#!/usr/bin/env bash
# Batch 293: Three missing Boston Weatherman activists; four distinct custody cases and one identified portrait.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-293.sh --dry-run
# sudo -u www-data bash database/data/run-batch-293.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-293.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch293.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 293 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
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
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted", "institution_id", "imprisoned_for_months"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255", "institution_id" => "sometimes|string", "imprisoned_for_months" => "sometimes|integer|in:18"])->validate();
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
    $expected = ["eric-m-mann" => ["name" => "Eric M. Mann", "affiliation" => ["Students for a Democratic Society", "Weathermen", "Congress of Racial Equality"], "state" => "Massachusetts", "era" => "1960s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "henry-a-olson" => ["name" => "Henry A. Olson", "affiliation" => ["Weathermen"], "state" => "Massachusetts", "era" => "1960s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null], "philip-c-nies" => ["name" => "Philip C. Nies", "affiliation" => ["Weathermen"], "state" => "Massachusetts", "era" => "1960s", "lat" => 42.36, "lng" => -71.06, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["eric-m-mann" => [["arrest_date" => ["year" => 1969, "month" => 10, "day" => 24], "incarceration_date" => ["year" => 1969, "month" => 10, "day" => 25], "release_date" => ["year" => 1969, "month" => 10, "day" => 29], "sentenced_date" => ["year" => 1969, "month" => 11, "day" => 7]], ["arrest_date" => ["year" => 1969, "month" => 10, "day" => 24], "incarceration_date" => ["year" => 1970, "month" => 1, "day" => 23], "sentenced_date" => ["year" => 1970, "month" => 1, "day" => 23], "release_date" => ["year" => 1971]]], "henry-a-olson" => [["arrest_date" => ["year" => 1969, "month" => 10, "day" => 24], "incarceration_date" => ["year" => 1969, "month" => 10, "day" => 25], "release_date" => ["year" => 1969, "month" => 10, "day" => 29], "sentenced_date" => ["year" => 1969, "month" => 11, "day" => 7]]], "philip-c-nies" => [["arrest_date" => ["year" => 1969, "month" => 10, "day" => 24], "incarceration_date" => ["year" => 1969, "month" => 10, "day" => 25], "release_date" => ["year" => 1969, "month" => 10, "day" => 29], "sentenced_date" => ["year" => 1969, "month" => 11, "day" => 7]]]];
    $reviewedVitals = ["eric-m-mann" => ["birthdate" => ["year" => 1942]], "henry-a-olson" => [], "philip-c-nies" => []];
    $reviewedCases = ["eric-m-mann" => [["charges" => "Boston English High School: assault and battery; earlier dangerous-weapon allegation did not proceed", "convicted" => "November 7, 1969: misdemeanor assault and battery; appealed", "sentence" => "Four days in pretrial custody. Later sentenced to three months, with concurrent terms where applicable; released on bond pending appeal. Service of that sentence is unverified.", "institution_id" => "da9d7982-8efe-464a-9748-a305cf979c22"], ["charges" => "Harvard Center for International Affairs: three counts of assault and battery; disturbing the peace", "convicted" => "Assault and battery convictions upheld after de novo trial; further appeal rejected June 15, 1971", "sentence" => "Two-year custodial sentence after the January 23, 1970 trial; additional assault sentences suspended with three years of probation following custody. Mann described a year and a half actually served, including the final nine months at Concord State Prison. Exact release day and month are unverified.", "imprisoned_for_months" => 18]], "henry-a-olson" => [["charges" => "Boston English High School: assault and battery; earlier dangerous-weapon allegation did not proceed", "convicted" => "November 7, 1969: misdemeanor assault and battery; appealed", "sentence" => "Four days in pretrial custody. Later sentenced to three months, with concurrent terms where applicable; released on bond pending appeal. Service of that sentence is unverified.", "institution_id" => "da9d7982-8efe-464a-9748-a305cf979c22"]], "philip-c-nies" => [["charges" => "Boston English High School: assault and battery; earlier dangerous-weapon allegation did not proceed", "convicted" => "November 7, 1969: misdemeanor assault and battery; appealed", "sentence" => "Four days in pretrial custody. Later sentenced to three months, with concurrent terms where applicable; released on bond pending appeal. Service of that sentence is unverified.", "institution_id" => "da9d7982-8efe-464a-9748-a305cf979c22"]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["eric-m-mann" => ["source_file" => "database/data/photos/eric-mann-usc-la-rising-2012.jpg", "storage_path" => "prisoners/eric-mann-usc-la-rising-2012.jpg", "sha256" => "89b097e12c50bf36bb43a6e4953e0f71f039eea903ce8d1f7837e790f725a040", "source_id" => "usc_profile"], "henry-a-olson" => null, "philip-c-nies" => null];
    if (($entry["photo"] ?? null) !== $approvedPhotos[$entry["key"]]) { throw new \RuntimeException("Unverified portrait."); }
    if (isset($entry["photo"])) {
        $checkSources([$entry["photo"]["source_id"]]);
        if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }
    }


}
if (($payload["expected_case_count"] ?? null) !== 4 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 4) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B293-OK\n";
'
run "add-affiliation-prisoners" "B293-OK" "$ADD_CODE" || exit 1
echo "Batch 293 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
