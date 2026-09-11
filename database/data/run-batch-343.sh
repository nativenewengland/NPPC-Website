#!/usr/bin/env bash
# Batch 343: Five verified Mississippi civil-rights prisoners; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-343.sh --dry-run
# sudo -u www-data bash database/data/run-batch-343.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-343.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch343.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 343 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 343."); }
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
    $expected = ["hartman-turnbow" => ["name" => "Hartman Turnbow", "first_name" => "Hartman", "middle_name" => null, "last_name" => "Turnbow", "aka" => null, "description" => "Hartman Turnbow was a Holmes County farmer and voting-rights organizer. After attackers firebombed his home, authorities accused him of arson and jailed him. In sworn testimony he described spending two days in custody before being bonded out.", "affiliation" => ["Mississippi Freedom Democratic Party", "Civil rights movement"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "euvester-simpson" => ["name" => "Euvester Simpson", "first_name" => "Euvester", "middle_name" => null, "last_name" => "Simpson", "aka" => null, "description" => "Euvester Simpson was a Mississippi SNCC organizer jailed with Fannie Lou Hamer and other civil-rights workers in Winona in June 1963. Her oral history describes several nights in custody and caring for Hamer after the jail beatings.", "affiliation" => ["Student Nonviolent Coordinating Committee", "Mississippi Freedom Democratic Party"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "unita-blackwell" => ["name" => "Unita Blackwell", "first_name" => "Unita", "middle_name" => null, "last_name" => "Blackwell", "aka" => null, "description" => "Unita Blackwell was a Mississippi voting-rights organizer and MFDP delegate who later became mayor of Mayersville. A contemporary profile records eleven days jailed in Jackson for her civil-rights work.", "affiliation" => ["Student Nonviolent Coordinating Committee", "Mississippi Freedom Democratic Party"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "john-ball" => ["name" => "John Ball", "first_name" => "John", "middle_name" => null, "last_name" => "Ball", "aka" => null, "description" => "John Ball was a SNCC worker jailed in Holmes County, Mississippi in May 1963 following the firebombing of voting-rights organizer Hartman Turnbow’s home. A contemporary SNCC newsletter names the detainees and reports their release after the arson charges were dropped.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "lavone-hampton" => ["name" => "Lavone Hampton", "first_name" => "Lavone", "middle_name" => null, "last_name" => "Hampton", "aka" => null, "description" => "Lavone Hampton was a SNCC worker jailed in Holmes County, Mississippi in May 1963 following the firebombing of voting-rights organizer Hartman Turnbow’s home. A contemporary SNCC newsletter names the detainees and reports their release after the arson charges were dropped.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["hartman-turnbow" => ["birthdate" => ["year" => 1905, "month" => 3, "day" => 20], "death_date" => ["year" => 1988, "month" => 8, "day" => 15]], "euvester-simpson" => ["birthdate" => ["year" => 1945, "month" => 12, "day" => 12]], "unita-blackwell" => ["birthdate" => ["year" => 1933, "month" => 3, "day" => 18], "death_date" => ["year" => 2019, "month" => 5, "day" => 13]], "john-ball" => [], "lavone-hampton" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["hartman-turnbow" => [["arrest_date" => ["year" => 1963, "month" => 5, "day" => 8], "incarceration_date" => ["year" => 1963, "month" => 5, "day" => 8], "release_date" => ["year" => 1963, "month" => 5]]], "euvester-simpson" => [["arrest_date" => ["year" => 1963, "month" => 6, "day" => 9], "incarceration_date" => ["year" => 1963, "month" => 6, "day" => 9], "release_date" => ["year" => 1963, "month" => 6]]], "unita-blackwell" => [[]], "john-ball" => [["arrest_date" => ["year" => 1963, "month" => 5, "day" => 8], "incarceration_date" => ["year" => 1963, "month" => 5, "day" => 8], "release_date" => ["year" => 1963, "month" => 5, "day" => 13]]], "lavone-hampton" => [["arrest_date" => ["year" => 1963, "month" => 5, "day" => 8], "incarceration_date" => ["year" => 1963, "month" => 5, "day" => 8], "release_date" => ["year" => 1963, "month" => 5, "day" => 13]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["hartman-turnbow" => ["Hartman Turnbow"], "euvester-simpson" => ["Euvester Simpson"], "unita-blackwell" => ["Unita Blackwell"], "john-ball" => ["John Ball"], "lavone-hampton" => ["Lavone Hampton"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["hartman-turnbow" => [["charges" => "Arson allegation after the firebombing of his own home", "sentence" => "Arrested May 8, 1963. Sworn testimony describes two days jailed before bond and later dismissal. A newsletter reports a collective May 13 release; his individual release day remains unresolved."]], "euvester-simpson" => [["charges" => "Arrest following use of the segregated Winona bus terminal; exact individual charge unverified", "sentence" => "Arrested June 9, 1963 and held for several nights. Her oral history places release later that week, around Wednesday; exact release day is not asserted."]], "unita-blackwell" => [["charges" => "Detention for civil-rights activism in Jackson; exact charge unverified", "sentence" => "Eleven days actually jailed in Jackson, Mississippi. Individual custody dates and any formal sentence remain unverified."]], "john-ball" => [["charges" => "Arson allegation in the Hartman Turnbow home-fire investigation", "sentence" => "Held May 8–13, 1963 in Lexington, Mississippi. The contemporary SNCC newsletter reports release after arson charges were dropped."]], "lavone-hampton" => [["charges" => "Arson allegation in the Hartman Turnbow home-fire investigation", "sentence" => "Held May 8–13, 1963 in Lexington, Mississippi. The contemporary SNCC newsletter reports release after arson charges were dropped."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["hartman-turnbow" => ["source_file" => "database/data/photos/hartman-turnbow-crmvet-b343.jpg", "storage_path" => "prisoners/hartman-turnbow-crmvet-b343.jpg", "sha256" => "4d1117323a28cea8132f9163eefd411418be041b94f31771f74e394e5d663c44", "source_id" => "turnbow", "identity_evidence" => "Identified by the source caption and visually reviewed. Exact original pixels retained; see CREDITS-batch343.md."], "euvester-simpson" => ["source_file" => "database/data/photos/euvester-simpson-danny-lyon-b343-crop.png", "storage_path" => "prisoners/euvester-simpson-danny-lyon-b343-crop.png", "sha256" => "f1a60c82f239ace453fc4ae9685a0c4c74752e68dfb8704e462cd0f11d0b8148", "source_id" => "simpson", "identity_evidence" => "Identified by the source caption and visually reviewed. Exact original pixels retained; see CREDITS-batch343.md."], "unita-blackwell" => ["source_file" => "database/data/photos/unita-blackwell-jim-peppler-b343-crop.png", "storage_path" => "prisoners/unita-blackwell-jim-peppler-b343-crop.png", "sha256" => "2a06250997cf58e4680cdf49ab114eec8e0ee163ead8e97f1d8da4a122af3ab6", "source_id" => "blackwell", "identity_evidence" => "Identified by the source caption and visually reviewed. Exact original pixels retained; see CREDITS-batch343.md."], "john-ball" => null, "lavone-hampton" => null];
    if (($entry["photo"] ?? null) !== $reviewedPhotos[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed portrait."); }
    if (isset($entry["photo"]) && hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }

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
echo "B343-OK\n";
'
run "add-affiliation-prisoners" "B343-OK" "$ADD_CODE" || exit 1
echo "Batch 343 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
