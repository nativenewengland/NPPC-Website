#!/usr/bin/env bash
# Batch 297: Eight verified historical Nationalist prisoners; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-297.sh --dry-run
# sudo -u www-data bash database/data/run-batch-297.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-297.sh [--dry-run]" >&2
    exit 2
fi
nppc_psysh_dir="$(pwd)/storage/framework/psysh"
if ! (umask 077; mkdir -p "$nppc_psysh_dir/config" "$nppc_psysh_dir/data" "$nppc_psysh_dir/runtime"); then
    echo "Cannot prepare PsySH storage; run this batch as the application owner." >&2
    exit 1
fi
run() {
    local label="$1" sentinel="$2" code="$3" out status=0
    echo "--- ${label}"
    out=$(XDG_CONFIG_HOME="$nppc_psysh_dir/config" \
        XDG_DATA_HOME="$nppc_psysh_dir/data" \
        XDG_RUNTIME_DIR="$nppc_psysh_dir/runtime" \
        php artisan tinker --execute="$code" 2>&1) || status=$?
    printf '%s\n' "$out"
    if [[ $status -ne 0 ]] || ! grep -Fxq "$sentinel" <<<"$out"; then
        echo "FAILED: ${label}" >&2
        return 1
    fi
}
ADD_CODE='
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Models\Institution;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch297.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 297 || ($payload["expected_count"] ?? null) !== 8 || count($payload["entries"] ?? []) !== 8) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($v) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $v))));
$checkDates = function ($dates, $allowed) {
    foreach ($dates as $field => $parts) {
        if (! in_array($field, $allowed, true) || ! is_array($parts) || array_diff(array_keys($parts), ["year", "month", "day"])) { throw new \RuntimeException("Unsupported date field or precision."); }
        Validator::make($parts, ["year" => "required|integer|between:1800,2026", "month" => "sometimes|integer|between:1,12", "day" => "sometimes|integer|between:1,31"])->validate();
        if ((isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) { throw new \RuntimeException("Invalid partial date."); }
    }
};
$checkSources = function ($ids) use ($payload) {
    if (! is_array($ids) || count($ids) === 0) { throw new \RuntimeException("Missing source references."); }
    foreach ($ids as $id) {
        if (! isset($payload["sources"][$id]["label"], $payload["sources"][$id]["url"]) || ! filter_var($payload["sources"][$id]["url"], FILTER_VALIDATE_URL) || ! preg_match("~^https?://~", $payload["sources"][$id]["url"])) { throw new \RuntimeException("Invalid source reference."); }
    }
};
$checkCase = function ($case) {
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string"])->validate();
};
foreach ($payload["institutions"] ?? [] as $id => $name) { if (Institution::whereKey($id)->value("name") !== $name) { throw new \RuntimeException("Institution identity mismatch."); } }
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1930s,1950s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka", "website", "gender", "race", "inmate_number"])) { throw new \RuntimeException("Unexpected profile field."); }
    if (isset($seen[$entry["key"]])) { throw new \RuntimeException("Duplicate batch key."); }
    $seen[$entry["key"]] = true;
    $names = array_unique(array_map($normalize, $entry["match_names"]));
    if (! in_array($normalize($entry["prisoner"]["name"]), $names, true)) { throw new \RuntimeException("Missing canonical match name."); }
    foreach ($names as $name) {
        if (count(explode(" ", $name)) < 2) { throw new \RuntimeException("Unsafe one-word identity key."); }
        $tokens = explode(" ", $name);
        sort($tokens);
        $identityKey = implode(" ", $tokens);
        if (isset($seenNames[$identityKey]) && $seenNames[$identityKey] !== $entry["key"]) { throw new \RuntimeException("Overlapping batch identities."); }
        $seenNames[$identityKey] = $entry["key"];
    }
    $checkDates($entry["dates"], ["birthdate", "death_date"]);

    if (isset($entry["dates"]["birthdate"], $entry["dates"]["death_date"]) && $entry["dates"]["birthdate"]["year"] > $entry["dates"]["death_date"]["year"]) { throw new \RuntimeException("Death precedes birth."); }
    if (count($entry["case_dates"]) !== count($entry["prisoner"]["cases"]) || count($entry["case_research"] ?? []) !== count($entry["case_dates"])) { throw new \RuntimeException("Case evidence/date count mismatch."); }
    foreach ($entry["prisoner"]["cases"] as $index => $case) {
        $checkCase($case);
        $checkDates($entry["case_dates"][$index], ["arrest_date", "incarceration_date", "release_date", "sentenced_date", "death_in_custody_date"]);
        $checkSources($entry["case_research"][$index]["source_ids"] ?? []);
        if (empty($entry["case_research"][$index]["custody_evidence"])) { throw new \RuntimeException("Missing episode custody evidence."); }
        if (! empty($case["institution_id"]) && ! isset($payload["institutions"][$case["institution_id"]])) { throw new \RuntimeException("Unverified institution."); }
    }
    $checkSources($entry["source_ids"] ?? []);
    if (empty($entry["custody_evidence"])) { throw new \RuntimeException("Missing actual custody evidence."); }
    $expected = ["juan-jaca-hernandez" => ["name" => "Juan Jaca Hernández", "first_name" => "Juan", "middle_name" => null, "last_name" => "Jaca Hernández", "aka" => null, "description" => "Juan Jaca Hernández was an Arecibo commander of the Cadets of the Republic. Imprisoned after the 1950 Nationalist uprising, he remained in custody in 1967. His imprisonment lasted about eighteen years, including confinement at La Princesa and Oso Blanco. He was pardoned in December 1968.", "affiliation" => ["Cadets of the Republic", "Puerto Rican Nationalist Party"], "state" => "Puerto Rico", "era" => "1950s", "lat" => 18.375, "lng" => -66.625, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "raimundo-diaz-pacheco" => ["name" => "Raimundo Díaz Pacheco", "first_name" => "Raimundo", "middle_name" => null, "last_name" => "Díaz Pacheco", "aka" => null, "description" => "Raimundo Díaz Pacheco commanded the Cadets of the Republic. The 1937 Cooper prosecution led to confinement at La Princesa and Leavenworth. He later returned to Puerto Rico and was killed during the October 30, 1950 attack on La Fortaleza.", "affiliation" => ["Cadets of the Republic", "Puerto Rican Nationalist Party"], "state" => "Kansas", "era" => "1930s", "lat" => 39.319056, "lng" => -94.913971, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => "52500"], "manuel-avila" => ["name" => "Manuel Ávila", "first_name" => "Manuel", "middle_name" => null, "last_name" => "Ávila", "aka" => null, "description" => "Manuel Ávila was a Puerto Rican independence supporter imprisoned at Lewisburg. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Pennsylvania", "era" => "1930s", "lat" => 40.964421, "lng" => -76.884795, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "juan-bautista-colon-rivera" => ["name" => "Juan Bautista Colón Rivera", "first_name" => "Juan Bautista", "middle_name" => null, "last_name" => "Colón Rivera", "aka" => null, "description" => "Juan Bautista Colón Rivera was a Puerto Rican independence supporter imprisoned at Lewisburg. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Pennsylvania", "era" => "1930s", "lat" => 40.964421, "lng" => -76.884795, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "dionisio-velez-aviles" => ["name" => "Dionisio Vélez Avilés", "first_name" => "Dionisio", "middle_name" => null, "last_name" => "Vélez Avilés", "aka" => null, "description" => "Dionisio Vélez Avilés was a Puerto Rican independence supporter imprisoned at Lewisburg. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Pennsylvania", "era" => "1930s", "lat" => 40.964421, "lng" => -76.884795, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "santiago-nieves-marzan" => ["name" => "Santiago Nieves Marzán", "first_name" => "Santiago", "middle_name" => null, "last_name" => "Nieves Marzán", "aka" => null, "description" => "Santiago Nieves Marzán was a Puerto Rican independence supporter imprisoned at Lewisburg. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Pennsylvania", "era" => "1930s", "lat" => 40.964421, "lng" => -76.884795, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "julio-monge-hernandez" => ["name" => "Julio Monge Hernández", "first_name" => "Julio", "middle_name" => null, "last_name" => "Monge Hernández", "aka" => null, "description" => "Julio Monge Hernández was a Puerto Rican independence supporter imprisoned at Lewisburg. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Pennsylvania", "era" => "1930s", "lat" => 40.964421, "lng" => -76.884795, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "juan-alamo-diaz" => ["name" => "Juan Álamo Díaz", "first_name" => "Juan", "middle_name" => null, "last_name" => "Álamo Díaz", "aka" => null, "description" => "Juan Álamo Díaz was a Puerto Rican independence supporter imprisoned at Leavenworth. Executive Order 7731 identifies him in the 1937 federal prosecution of Julio Pinto Gandía and others. The 1939 solidarity committee roster confirms his actual imprisonment.", "affiliation" => ["Puerto Rican Nationalist Party"], "state" => "Kansas", "era" => "1930s", "lat" => 39.319056, "lng" => -94.913971, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["juan-jaca-hernandez" => [["arrest_date" => ["year" => 1950], "sentenced_date" => ["year" => 1951, "month" => 4], "release_date" => ["year" => 1968]]], "raimundo-diaz-pacheco" => [[]], "manuel-avila" => [[]], "juan-bautista-colon-rivera" => [[]], "dionisio-velez-aviles" => [[]], "santiago-nieves-marzan" => [[]], "julio-monge-hernandez" => [[]], "juan-alamo-diaz" => [[]]];
    $reviewedVitals = ["juan-jaca-hernandez" => ["birthdate" => ["year" => 1909], "death_date" => ["year" => 1994]], "raimundo-diaz-pacheco" => ["birthdate" => ["year" => 1906, "month" => 12, "day" => 27], "death_date" => ["year" => 1950, "month" => 10, "day" => 30]], "manuel-avila" => [], "juan-bautista-colon-rivera" => [], "dionisio-velez-aviles" => [], "santiago-nieves-marzan" => [], "julio-monge-hernandez" => [], "juan-alamo-diaz" => []];
    $reviewedCases = ["juan-jaca-hernandez" => [["charges" => "Four counts of first-degree murder and six counts of assault with intent to commit murder, arising from the 1950 Arecibo uprising", "convicted" => "Convicted in April 1951; judgments affirmed November 30, 1954", "sentence" => "Four life terms and six terms of six to fourteen years. The 1967 federal habeas decision confirms actual continuing imprisonment. Pardoned December 27, 1968; exact physical release day remains unverified. A separate Law 53 sentence was imposed September 18, 1952 during the same confinement and is not counted as another imprisonment episode."]], "raimundo-diaz-pacheco" => [["charges" => "Conspiracy to murder federal judge Robert A. Cooper", "convicted" => "Convicted in the federal Cooper prosecution", "sentence" => "Five-year prison sentence. First-person testimony records seven months in La Princesa before federal imprisonment; a 1939 roster locates him at Leavenworth. His term expired January 9, 1943, but no exact physical release date is entered.", "institution_id" => "6293d22d-c3ac-40cf-b14c-5dd2cf1c1469"]], "manuel-avila" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "df689c4b-2900-4bb4-bb53-55fd5fd1abd7"]], "juan-bautista-colon-rivera" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "df689c4b-2900-4bb4-bb53-55fd5fd1abd7"]], "dionisio-velez-aviles" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "df689c4b-2900-4bb4-bb53-55fd5fd1abd7"]], "santiago-nieves-marzan" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "df689c4b-2900-4bb4-bb53-55fd5fd1abd7"]], "julio-monge-hernandez" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "df689c4b-2900-4bb4-bb53-55fd5fd1abd7"]], "juan-alamo-diaz" => [["charges" => "Federal political prosecution in United States v. Julio Pinto Gandia et al., No.4456 Cr.; exact conviction statute unverified", "convicted" => "Named as a sentenced political prisoner in the contemporary 1939 roster", "sentence" => "Actual federal penitentiary confinement is documented. Individual commitment and physical release dates, exact time served, and the final judgment text remain unverified.", "institution_id" => "6293d22d-c3ac-40cf-b14c-5dd2cf1c1469"]]];
    $reviewedNames = ["juan-jaca-hernandez" => ["Juan Jaca Hernández", "Juan Jaca"], "raimundo-diaz-pacheco" => ["Raimundo Díaz Pacheco", "Raimundo Dias Pacheco", "Ramón Díaz Pacheco"], "manuel-avila" => ["Manuel Ávila"], "juan-bautista-colon-rivera" => ["Juan Bautista Colón Rivera", "Juan Bautista Colón"], "dionisio-velez-aviles" => ["Dionisio Vélez Avilés"], "santiago-nieves-marzan" => ["Santiago Nieves Marzán", "Santiago Nieves Malsán", "Santiago Nieves Malsan"], "julio-monge-hernandez" => ["Julio Monge Hernández", "Julio Mange Hernandez"], "juan-alamo-diaz" => ["Juan Álamo Díaz", "Juan Alamo"]];
    if ($entry["match_names"] !== $reviewedNames[$entry["key"]]) { throw new \RuntimeException("Unreviewed identity matching."); }
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait verified for this batch."); }


}
if (($payload["expected_case_count"] ?? null) !== 8 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 8) { throw new \RuntimeException("Unexpected total case count."); }
$result = DB::transaction(function () use ($payload, $normalize, $dryRun) {
    $records = Prisoner::withoutGlobalScopes()->get();
    $missing = [];
    $preserved = 0;
    foreach ($payload["entries"] as $entry) {
        $names = array_unique(array_map($normalize, $entry["match_names"]));
        $matches = $records->filter(function ($record) use ($names, $normalize) {
            $haystack = " ".$normalize(implode(" ", [$record->name, $record->aka, $record->first_name, $record->middle_name, $record->last_name, $record->slug]))." ";
            foreach ($names as $name) {
                $found = true;
                foreach (explode(" ", $name) as $token) { if (! str_contains($haystack, " ".$token." ")) { $found = false; break; } }
                if ($found) { return true; }
            }
            return false;
        });
        if ($matches->count() > 1) { throw new \RuntimeException("Ambiguous identity: ".$entry["prisoner"]["name"]); }
        if ($matches->isNotEmpty()) {
            echo "Preserved existing: ", $entry["prisoner"]["name"], "\n";
            $preserved++;
        } else { $missing[] = $entry; }
    }
    $nextOrder = (int) $records->max("sort_order") + 1;
    foreach ($missing as $entry) {
        echo ($dryRun ? "Would add: " : "Adding: "), $entry["prisoner"]["name"], "\n";
        if ($dryRun) { continue; }
        $fields = $entry["prisoner"];
        $cases = $fields["cases"];
        unset($fields["cases"]);
        if (isset($entry["photo"])) {
            $photo = $entry["photo"];
            $disk = Storage::disk("public");
            if ($disk->exists($photo["storage_path"])) {
                if (hash("sha256", $disk->get($photo["storage_path"])) !== $photo["sha256"]) { throw new \RuntimeException("Refusing to overwrite different portrait bytes."); }
            } elseif (! $disk->put($photo["storage_path"], File::get(base_path($photo["source_file"])))) { throw new \RuntimeException("Could not store portrait."); }
            $fields["photo"] = $photo["storage_path"];
        }
        $record = new Prisoner($fields);
        $record->sort_order = $nextOrder++;
        foreach ($entry["dates"] as $field => $parts) { $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        $record->save();
        foreach ($cases as $index => $caseFields) {
            $case = new PrisonerCase($caseFields);
            $case->prisoner_id = $record->id;
            foreach ($entry["case_dates"][$index] as $field => $parts) { $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
            $case->save();
        }
    }
    return [count($missing), $preserved];
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo ($dryRun ? "Would add profiles: " : "Added profiles: "), $result[0], "; existing profiles preserved: ", $result[1], "\n";
echo "B297-OK\n";
'
run "add-affiliation-prisoners" "B297-OK" "$ADD_CODE" || exit 1
echo "Batch 297 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
