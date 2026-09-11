#!/usr/bin/env bash
# Batch 331: Two verified Irish republican prisoners; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-331.sh --dry-run
# sudo -u www-data bash database/data/run-batch-331.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-331.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch331.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 331 || ($payload["expected_count"] ?? null) !== 2 || count($payload["entries"] ?? []) !== 2) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 331."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1970s,1990s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["michael-o-rourke" => ["name" => "Michael O\u{0027}Rourke", "first_name" => "Michael", "middle_name" => null, "last_name" => "O\u{0027}Rourke", "aka" => "Michael ORourke; Patrick Mannion", "description" => "Michael O\u{0027}Rourke was an Irish republican who sought to remain in the United States after escaping from imprisonment in Ireland. US authorities held him for years in civil immigration detention while he contested deportation. In June 1984 he was returned to Ireland and taken into Irish custody.", "affiliation" => ["Irish Republican Army"], "state" => "New York", "era" => "1970s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "gerry-mcgeough" => ["name" => "Gerry McGeough", "first_name" => "Gerry", "middle_name" => null, "last_name" => "McGeough", "aka" => "Terence Gerard McGeough; Gerard McGeough; Terence McGeough", "description" => "Gerry McGeough is an Irish republican who was extradited from Germany to the United States to face weapons charges connected to the IRA. He pleaded guilty and served a US prison term before returning to Ireland in 1996. His German and later Northern Irish imprisonment are separate from this US case.", "affiliation" => ["Irish Republican Army"], "state" => "Pennsylvania", "era" => "1990s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["michael-o-rourke" => [], "gerry-mcgeough" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["michael-o-rourke" => [["arrest_date" => ["year" => 1979, "month" => 10, "day" => 30], "incarceration_date" => ["year" => 1979, "month" => 10, "day" => 30], "release_date" => ["year" => 1984, "month" => 6]]], "gerry-mcgeough" => [["incarceration_date" => ["year" => 1994, "month" => 4], "release_date" => ["year" => 1996]], ["incarceration_date" => ["year" => 1992, "month" => 5, "day" => 28], "release_date" => ["year" => 1992]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["michael-o-rourke" => ["Michael O\u{0027}Rourke", "Michael ORourke", "Patrick Mannion"], "gerry-mcgeough" => ["Gerry McGeough", "Terence Gerard McGeough", "Gerard McGeough", "Terence McGeough"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["michael-o-rourke" => [["charges" => "Civil immigration detention pending deportation for overstaying a visa; no US criminal conviction in this proceeding", "sentence" => "Held without bail from 1979 until deportation in June 1984. His IRA history and escape from Irish prison figured in the bail litigation. The endpoint records departure from US detention into Irish custody, not release to freedom. The Irish criminal sentence is not a US sentence."]], "gerry-mcgeough" => [["charges" => "Unlicensed interstate movement of weapons in an IRA arms-procurement prosecution", "sentence" => "A three-year term is reported in contemporary accounts; a later appellate history describes a two-year sentence. Research places commencement of the prison term in spring 1994 and return to Ireland in 1996. Earlier bail time is excluded. The exact sentencing day, release day and actual total served remain unresolved."], ["charges" => "US detention on extradition for the pending IRA weapons prosecution", "sentence" => "After extradition on May 28, 1992, McGeough was held at the Manhattan Correction Centre before obtaining bail. His later interview describes ten days there. This pretrial episode is separate from his later prison term and excludes German custody and the interval on bail."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch331."); }

}
if (($payload["expected_case_count"] ?? null) !== 3 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 3) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B331-OK\n";
'
run "add-affiliation-prisoners" "B331-OK" "$ADD_CODE" || exit 1
echo "Batch 331 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
