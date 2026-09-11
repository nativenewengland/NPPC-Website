#!/usr/bin/env bash
# Batch 317: Five verified Bay Area political detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-317.sh --dry-run
# sudo -u www-data bash database/data/run-batch-317.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-317.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch317.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 317 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 317."); }
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
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id", "imprisoned_for_months"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string", "imprisoned_for_months" => "sometimes|integer|min:1"])->validate();
};
foreach ($payload["institutions"] ?? [] as $id => $name) { if (Institution::whereKey($id)->value("name") !== $name) { throw new \RuntimeException("Institution identity mismatch."); } }
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1960s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["david-lance-goines" => ["name" => "David Lance Goines", "first_name" => "David", "middle_name" => "Lance", "last_name" => "Goines", "aka" => "David Goines; David L. Goines", "description" => "David Lance Goines was a Berkeley printer, artist and Free Speech Movement participant. He printed movement literature and was jailed following the Sproul Hall sit-in. He later founded Saint Hieronymus Press.", "affiliation" => ["Free Speech Movement"], "state" => "California", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => "https://www.goines.net/", "gender" => null, "race" => null, "inmate_number" => null], "kipp-dawson" => ["name" => "Kipp Dawson", "first_name" => "Kipp", "middle_name" => null, "last_name" => "Dawson", "aka" => "Kipp M. Dawson", "description" => "Kipp Dawson organized civil-rights sit-ins against discriminatory hiring in San Francisco and helped organize the Free Speech Movement at San Francisco State. She served 29 days in jail in 1966 and later worked in antiwar and labor organizing.", "affiliation" => ["Ad Hoc Committee to End Discrimination", "Free Speech Movement", "Vietnam Day Committee"], "state" => "California", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => "https://kippdawson.com/", "gender" => null, "race" => null, "inmate_number" => null], "michael-dale-rossman" => ["name" => "Michael Dale Rossman", "first_name" => "Michael", "middle_name" => "Dale", "last_name" => "Rossman", "aka" => "Michael Rossman; Mike Rossman", "description" => "Michael Rossman was a Berkeley Free Speech Movement organizer, writer and later collector of political posters. He spent nine weeks at Santa Rita in 1967 following the movement prosecution and wrote about prison life.", "affiliation" => ["Free Speech Movement", "SLATE"], "state" => "California", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "patricia-iiyama" => ["name" => "Patricia Iiyama", "first_name" => "Patricia", "middle_name" => null, "last_name" => "Iiyama", "aka" => "Patti Iiyama; Pat Iiyama", "description" => "Patricia “Patti” Iiyama was a civil-rights activist and member of the Berkeley Free Speech Movement executive committee. Following the Sproul Hall sit-in, she refused probation and experienced a thirty-day term at Santa Rita.", "affiliation" => ["Free Speech Movement", "SLATE", "Women for Peace"], "state" => "California", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "michael-henry-marcus" => ["name" => "Michael Henry Marcus", "first_name" => "Michael", "middle_name" => "Henry", "last_name" => "Marcus", "aka" => "Michael Marcus; Michael H. Marcus", "description" => "Michael Henry Marcus was an anti-nuclear protester and Berkeley Free Speech Movement participant who later became an Oregon judge. He recalled serving thirty days at Santa Rita following an Atomic Energy Commission sit-in.", "affiliation" => ["Free Speech Movement"], "state" => "California", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["david-lance-goines" => ["birthdate" => ["year" => 1945, "month" => 5, "day" => 29], "death_date" => ["year" => 2023, "month" => 2, "day" => 19]], "kipp-dawson" => ["birthdate" => ["year" => 1945]], "michael-dale-rossman" => ["birthdate" => ["year" => 1939, "month" => 12, "day" => 15], "death_date" => ["year" => 2008, "month" => 5, "day" => 12]], "patricia-iiyama" => ["birthdate" => ["year" => 1945]], "michael-henry-marcus" => ["birthdate" => ["year" => 1943, "month" => 4, "day" => 11], "death_date" => ["year" => 2017, "month" => 7, "day" => 22]]];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["david-lance-goines" => [["arrest_date" => ["year" => 1964, "month" => 12, "day" => 3], "incarceration_date" => ["year" => 1967], "release_date" => ["year" => 1967]]], "kipp-dawson" => [["incarceration_date" => ["year" => 1966], "release_date" => ["year" => 1966]]], "michael-dale-rossman" => [["incarceration_date" => ["year" => 1967], "release_date" => ["year" => 1967]]], "patricia-iiyama" => [["arrest_date" => ["year" => 1964, "month" => 12], "sentenced_date" => ["year" => 1965]]], "michael-henry-marcus" => [[]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["david-lance-goines" => ["David Lance Goines", "David Goines", "David L. Goines"], "kipp-dawson" => ["Kipp Dawson", "Kipp M. Dawson"], "michael-dale-rossman" => ["Michael Dale Rossman", "Michael Rossman", "Mike Rossman"], "patricia-iiyama" => ["Patricia Iiyama", "Patti Iiyama", "Pat Iiyama"], "michael-henry-marcus" => ["Michael Henry Marcus", "Michael Marcus", "Michael H. Marcus"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["david-lance-goines" => [["charges" => "Sproul Hall sit-in prosecution — individual statutory counts not verified", "sentence" => "Arrested December 3, 1964. Subsequent confinement at Santa Rita is confirmed by accounts of repeated family visits. The FSM Archives dates his jail term to 1967. The Chronicle reports a thirty-day sentence; the full judgment and exact custody endpoints remain unverified."]], "kipp-dawson" => [["charges" => "Civil-rights sit-in prosecution — exact individual statutory counts unverified", "sentence" => "Served 29 days in jail in 1966 following sit-ins against discriminatory employment practices in San Francisco. Individual arrest, sentencing and release days and the precise jail remain unverified. This is not entered as the December 1964 UC Berkeley Sproul Hall case."]], "michael-dale-rossman" => [["charges" => "Free Speech Movement sit-in prosecution — exact individual statutory counts unverified", "sentence" => "His February 1968 first-person account records nine weeks at Santa Rita during the previous summer, after the Supreme Court declined review of the movement case. The custody year is 1967; exact entry and release days remain unverified."]], "patricia-iiyama" => [["charges" => "Trespassing and resisting arrest in the Sproul Hall sit-in prosecution", "sentence" => "Convicted in summer 1965 after the December 1964 sit-in. She refused probation and received a thirty-day Santa Rita term; her later account describes the confinement. The exact dates she entered and left jail remain unresolved and are not equated with her conviction date."]], "michael-henry-marcus" => [["charges" => "Obstructing an exit in violation of the fire code and disturbing the peace — oral-history description", "sentence" => "Marcus recalled convictions on two counts after an anti-nuclear Atomic Energy Commission sit-in; unlawful assembly was dismissed. He served thirty days at Santa Rita. The introductory summary conflates this protest with a separate submarine-launch action, so the custody year and exact endpoints are left blank."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["david-lance-goines" => null, "kipp-dawson" => ["source_file" => "database/data/photos/kipp-dawson-school-portrait.png", "storage_path" => "prisoners/kipp-dawson-school-portrait.png", "sha256" => "e2412f5a46dfe379e20c5a2b73885aae4408c25cae8e5e76c86fc19bc4d22c2f", "source_ids" => ["dawson-photo"], "identity_evidence" => "The personal archive identifies this image as a school photograph of young Kipp Dawson in a turtleneck; visually inspected.", "credit" => "Courtesy of the Kipp Dawson personal archive; photographer unidentified. No public-domain or Creative Commons license asserted.", "changes" => "Original downloaded PNG retained unchanged; no cropping, enhancement or redraw."], "michael-dale-rossman" => null, "patricia-iiyama" => null, "michael-henry-marcus" => null];
    if (($entry["photo"] ?? null) !== $reviewedPhotos[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed portrait."); }
    if (isset($entry["photo"])) { $checkSources($entry["photo"]["source_ids"]); if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait bytes differ from review."); } }

}
if (($payload["expected_case_count"] ?? null) !== 5 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 5) { throw new \RuntimeException("Unexpected total case count."); }
$result = DB::transaction(function () use ($payload, $normalize, $dryRun) {
    $records = Prisoner::withoutGlobalScopes()->get(["id", "name", "aka", "first_name", "middle_name", "last_name", "slug", "sort_order"]);
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
echo "B317-OK\n";
'
run "add-affiliation-prisoners" "B317-OK" "$ADD_CODE" || exit 1
echo "Batch 317 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
