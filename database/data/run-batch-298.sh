#!/usr/bin/env bash
# Batch 298: Six verified Fort Deposit detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-298.sh --dry-run
# sudo -u www-data bash database/data/run-batch-298.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-298.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch298.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 298 || ($payload["expected_count"] ?? null) !== 6 || count($payload["entries"] ?? []) !== 6) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1960s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["richard-morrisroe" => ["name" => "Richard Morrisroe", "first_name" => "Richard", "middle_name" => null, "last_name" => "Morrisroe", "aka" => null, "description" => "Richard Morrisroe was a Chicago priest and Catholic Interracial Council member. He survived a shooting shortly after his six-day Hayneville detention.", "affiliation" => ["Catholic Interracial Council", "Student Nonviolent Coordinating Committee"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "jonathan-myrick-daniels" => ["name" => "Jonathan Myrick Daniels", "first_name" => "Jonathan", "middle_name" => "Myrick", "last_name" => "Daniels", "aka" => null, "description" => "Jonathan Myrick Daniels was an Episcopal seminarian and civil rights worker. After six days in jail, he was killed protecting Ruby Sales.", "affiliation" => ["Episcopal Society for Cultural and Racial Unity"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "ruby-sales" => ["name" => "Ruby Sales", "first_name" => "Ruby", "middle_name" => null, "last_name" => "Sales", "aka" => null, "description" => "Ruby Sales organized with SNCC in Alabama. Her 1965 imprisonment preceded the attack in which Jonathan Daniels saved her life.", "affiliation" => ["Student Nonviolent Coordinating Committee"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "joyce-bailey" => ["name" => "Joyce Bailey", "first_name" => "Joyce", "middle_name" => null, "last_name" => "Bailey", "aka" => null, "description" => "Joyce Bailey was a Fort Deposit civil rights protester detained at Hayneville. She survived the attack following the group\u{0027}s release.", "affiliation" => ["Civil Rights Movement"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "gloria-larry" => ["name" => "Gloria Larry", "first_name" => "Gloria", "middle_name" => null, "last_name" => "Larry", "aka" => null, "description" => "Gloria Larry came from California and joined the Fort Deposit protests. She was among the six named released detainees in SNCC\u{0027}s report.", "affiliation" => ["Civil Rights Movement"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "willie-vaughn" => ["name" => "Willie Vaughn", "first_name" => "Willie", "middle_name" => null, "last_name" => "Vaughn", "aka" => null, "description" => "Willie Vaughn participated in the Fort Deposit protests and was jailed at Hayneville. He escaped the shooting after his release.", "affiliation" => ["Civil Rights Movement"], "state" => "Alabama", "era" => "1960s", "lat" => 32.18222, "lng" => -86.57861, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["richard-morrisroe" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]], "jonathan-myrick-daniels" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]], "ruby-sales" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]], "joyce-bailey" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]], "gloria-larry" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]], "willie-vaughn" => [["arrest_date" => ["year" => 1965, "month" => 8, "day" => 14], "incarceration_date" => ["year" => 1965, "month" => 8, "day" => 14], "release_date" => ["year" => 1965, "month" => 8, "day" => 20]]]];
    $reviewedVitals = ["richard-morrisroe" => [], "jonathan-myrick-daniels" => ["birthdate" => ["year" => 1939, "month" => 3, "day" => 20], "death_date" => ["year" => 1965, "month" => 8, "day" => 20]], "ruby-sales" => ["birthdate" => ["year" => 1948, "month" => 7, "day" => 8]], "joyce-bailey" => [], "gloria-larry" => [], "willie-vaughn" => []];
    $reviewedNames = ["richard-morrisroe" => ["Richard Morrisroe", "Richard F. Morrisroe", "Dick Morrisroe", "Richard Morrisrew", "Richard Morriscoe"], "jonathan-myrick-daniels" => ["Jonathan Myrick Daniels", "Jonathan Daniels", "Jonathan M. Daniels", "Jon Daniels", "John Daniels"], "ruby-sales" => ["Ruby Sales", "Ruby Nell Sales"], "joyce-bailey" => ["Joyce Bailey"], "gloria-larry" => ["Gloria Larry"], "willie-vaughn" => ["Willie Vaughn", "Willie Vaugh"]];
    $reviewedCases = ["richard-morrisroe" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]], "jonathan-myrick-daniels" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]], "ruby-sales" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]], "joyce-bailey" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]], "gloria-larry" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]], "willie-vaughn" => [["charges" => "Disturbing the peace and parading without a permit (contemporary SNCC report)", "sentence" => "Pretrial detention at Lowndes County Jail, Hayneville; released August 20, 1965. No individual sentencing judgment verified."]]];
    if ($entry["match_names"] !== $reviewedNames[$entry["key"]] || $entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed identity, case or date."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait verified for this batch."); }


}
if (($payload["expected_case_count"] ?? null) !== 6 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 6) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B298-OK\n";
'
run "add-affiliation-prisoners" "B298-OK" "$ADD_CODE" || exit 1
echo "Batch 298 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
