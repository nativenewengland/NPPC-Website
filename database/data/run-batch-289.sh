#!/usr/bin/env bash
# Batch 289: Three missing Black Lives Matter protesters with actual pretrial custody.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-289.sh --dry-run
# sudo -u www-data bash database/data/run-batch-289.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-289.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch289.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 289 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:2020s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["denzel-draughn" => ["name" => "Denzel Draughn", "affiliation" => ["Black Lives Matter Movement"], "state" => "California", "era" => "2020s", "lat" => 32.72, "lng" => -117.16, "in_custody" => false, "released" => true, "website" => null, "gender" => "Male", "race" => "Black", "inmate_number" => null], "taylor-breann-enterline" => ["name" => "Taylor Breann Enterline", "affiliation" => ["Black Lives Matter Movement"], "state" => "Pennsylvania", "era" => "2020s", "lat" => 40.04, "lng" => -76.31, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => "Black", "inmate_number" => null], "kathryn-patterson-lancaster" => ["name" => "Kathryn Patterson", "affiliation" => ["Black Lives Matter Movement"], "state" => "Pennsylvania", "era" => "2020s", "lat" => 40.04, "lng" => -76.31, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewedDates = ["denzel-draughn" => [["arrest_date" => ["year" => 2020, "month" => 8, "day" => 28]]], "taylor-breann-enterline" => [["arrest_date" => ["year" => 2020, "month" => 9, "day" => 14], "incarceration_date" => ["year" => 2020, "month" => 9, "day" => 14], "release_date" => ["year" => 2020, "month" => 9, "day" => 17], "sentenced_date" => ["year" => 2023, "month" => 4, "day" => 4]]], "kathryn-patterson-lancaster" => [["arrest_date" => ["year" => 2020, "month" => 9, "day" => 14], "incarceration_date" => ["year" => 2020, "month" => 9, "day" => 14], "release_date" => ["year" => 2020, "month" => 9, "day" => 17], "sentenced_date" => ["year" => 2023, "month" => 3]]]];
    $reviewedVitals = ["denzel-draughn" => [], "taylor-breann-enterline" => [], "kathryn-patterson-lancaster" => []];
    $reviewedCases = ["denzel-draughn" => [["charges" => "Unlawful use of tear gas against peace officers and resisting/use of force against officers", "convicted" => "Acquitted of all charges by jury on December 9, 2021", "sentence" => "No sentence: acquitted. Detained following the August 28, 2020 arrest and still jailed at the September 9 bail hearing. Bail was reduced from $750,000 to $150,000. The later acquittal report confirms he was free, but does not establish the precise day he left jail. Earlier reports list differing charge totals as proceedings developed; no dismissed count is presented as a conviction.", "institution_id" => "ad08ad96-7cec-4d39-8b2f-05b542568a4d"]], "taylor-breann-enterline" => [["charges" => "Riot; failure to disperse; obstruction of highways; disorderly conduct; defiant trespass", "convicted" => "Jury conviction January 19, 2023; acquitted of conspiracy to commit riot", "sentence" => "Three years of probation imposed April 4, 2023, with restitution and community service; affirmed May 31, 2024. The qualifying imprisonment was September 2020 pretrial detention, not the probation term. The appellate court rejected her sufficiency and weight challenges; it did not require proof that she personally threw or broke objects."]], "kathryn-patterson-lancaster" => [["charges" => "Failure to disperse; obstructing highways; disorderly conduct; trespassing", "convicted" => "Guilty plea to four misdemeanors in March 2023; felony charges withdrawn", "sentence" => "Eighteen months of probation, a fine and 100 hours of community service. The prison database records her September 2020 pretrial detention, not probation as jail time. The exact sentencing day is not entered."]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["denzel-draughn" => ["source_file" => "database/data/photos/denzel-draughn-news8-crop.png", "storage_path" => "prisoners/denzel-draughn-news8-crop.png", "sha256" => "f477a200d01948763674a6a26063ea36d142c0f33183d1569979dbcc4050a4bf", "source_id" => "kpbs_bail"], "taylor-breann-enterline" => ["source_file" => "database/data/photos/taylor-enterline-lancaster-da.jpg", "storage_path" => "prisoners/taylor-enterline-lancaster-da.jpg", "sha256" => "9edcf57fdb34731873a4fd5fd9b154c41f67248abbb05f8aa5316d76ccec7656", "source_id" => "enterline_da"], "kathryn-patterson-lancaster" => null];
    if (($entry["photo"] ?? null) !== $approvedPhotos[$entry["key"]]) { throw new \RuntimeException("Unverified portrait."); }
    if (isset($entry["photo"])) {
        $checkSources([$entry["photo"]["source_id"]]);
        if (hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }
    }


}
if (($payload["expected_case_count"] ?? null) !== 3 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 3) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B289-OK\n";
'
run "add-affiliation-prisoners" "B289-OK" "$ADD_CODE" || exit 1
echo "Batch 289 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
