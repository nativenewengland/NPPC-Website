#!/usr/bin/env bash
# Batch 313: Five verified RAMPS and Tar Sands Blockade activists; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-313.sh --dry-run
# sudo -u www-data bash database/data/run-batch-313.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-313.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch313.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 313 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 313."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:2010s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["glen-collins" => ["name" => "Glen Collins", "first_name" => "Glen", "middle_name" => null, "last_name" => "Collins", "aka" => null, "description" => "Glen Collins participated in the December 2012 Keystone XL pipeline protest near Winona, Texas. The protest led to several weeks in Smith County Jail before release.", "affiliation" => ["Tar Sands Blockade", "Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "Texas", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => false, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "matthew-almonte" => ["name" => "Matthew Almonte", "first_name" => "Matthew", "middle_name" => null, "last_name" => "Almonte", "aka" => "Matt Almonte; Mathew Almonte", "description" => "Matthew Almonte participated in the December 2012 Keystone XL pipeline protest near Winona, Texas. The protest led to several weeks in Smith County Jail before release.", "affiliation" => ["Tar Sands Blockade"], "state" => "Texas", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "isabel-indigo-brooks" => ["name" => "Isabel Indigo Brooks", "first_name" => "Isabel", "middle_name" => "Indigo", "last_name" => "Brooks", "aka" => "Isabel Brooks; Isabelle Brooks", "description" => "Isabel Indigo Brooks participated in the December 2012 Keystone XL pipeline protest near Winona, Texas. The protest led to several weeks in Smith County Jail before release. Brooks documented the action from inside the pipe and later discussed the experience in an interview.", "affiliation" => ["Tar Sands Blockade"], "state" => "Texas", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "camilo-pereira" => ["name" => "Camilo Pereira", "first_name" => "Camilo", "middle_name" => null, "last_name" => "Pereira", "aka" => null, "description" => "Camilo Pereira participated in the June 2014 blockade of Alpha Natural Resources headquarters in Bristol, Virginia. Pereira was among eight activists who spent multiple days in jail while awaiting bond hearings.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)", "Mountain Justice"], "state" => "Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "catherine-ann-macdougal" => ["name" => "Catherine Ann MacDougal", "first_name" => "Catherine", "middle_name" => "Ann", "last_name" => "MacDougal", "aka" => "Catherine-Ann MacDougal; Catherine Anne MacDougal; Squirrel", "description" => "Catherine Ann MacDougal, known as Squirrel, participated in the Coal River Mountain tree sit and a coal-barge blockade in West Virginia. MacDougal wrote about jail conditions and the environmental campaign after custody in 2012.", "affiliation" => ["Radical Action for Mountains’ and People’s Survival (RAMPS)"], "state" => "West Virginia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["glen-collins" => [], "matthew-almonte" => [], "isabel-indigo-brooks" => [], "camilo-pereira" => [], "catherine-ann-macdougal" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["glen-collins" => [["arrest_date" => ["year" => 2012, "month" => 12, "day" => 3], "incarceration_date" => ["year" => 2012, "month" => 12, "day" => 3], "release_date" => ["year" => 2012, "month" => 12]], ["sentenced_date" => ["year" => 2013, "month" => 4, "day" => 24], "incarceration_date" => ["year" => 2013, "month" => 4, "day" => 24]]], "matthew-almonte" => [["arrest_date" => ["year" => 2012, "month" => 12, "day" => 3], "incarceration_date" => ["year" => 2012, "month" => 12, "day" => 3], "release_date" => ["year" => 2012, "month" => 12]]], "isabel-indigo-brooks" => [["arrest_date" => ["year" => 2012, "month" => 12, "day" => 3], "incarceration_date" => ["year" => 2012, "month" => 12, "day" => 3], "release_date" => ["year" => 2012, "month" => 12]]], "camilo-pereira" => [["arrest_date" => ["year" => 2014, "month" => 6, "day" => 20], "incarceration_date" => ["year" => 2014, "month" => 6, "day" => 20], "release_date" => ["year" => 2014]]], "catherine-ann-macdougal" => [["sentenced_date" => ["year" => 2012, "month" => 2, "day" => 9], "incarceration_date" => ["year" => 2012, "month" => 2, "day" => 9], "release_date" => ["year" => 2012, "month" => 2]], ["arrest_date" => ["year" => 2012, "month" => 5, "day" => 24], "incarceration_date" => ["year" => 2012, "month" => 5, "day" => 24], "release_date" => ["year" => 2012, "month" => 5]], ["sentenced_date" => ["year" => 2012, "month" => 11, "day" => 5], "incarceration_date" => ["year" => 2012, "month" => 11, "day" => 5], "release_date" => ["year" => 2012, "month" => 11]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["glen-collins" => ["Glen Collins"], "matthew-almonte" => ["Matthew Almonte", "Matt Almonte", "Mathew Almonte"], "isabel-indigo-brooks" => ["Isabel Indigo Brooks", "Isabel Brooks", "Isabelle Brooks"], "camilo-pereira" => ["Camilo Pereira"], "catherine-ann-macdougal" => ["Catherine Ann MacDougal", "Catherine-Ann MacDougal", "Catherine Anne MacDougal"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["glen-collins" => [["charges" => "Criminal trespass, resisting arrest and illegal dumping reported after Keystone XL blockade", "sentence" => "Arrested December 3, 2012 and held in Smith County Jail on a reported $65, 000 bond. The February 2013 interview reports twenty-four days served by the three defendants; the campaign also confirms release. The exact release day is unverified. This records the initial pretrial detention, not a sentence of twenty-four days."], ["charges" => "Criminal trespass and illegal dumping — Keystone XL blockade plea", "sentence" => "Returned to Smith County Jail April 24, 2013 after pleading guilty and receiving a sixty-day sentence. The campaign initially had not resolved how the earlier pretrial custody would be credited. A later update gives a conditional future May 7 release dependent on court-cost payment; no actual release date or full sixty days served is asserted."]], "matthew-almonte" => [["charges" => "Criminal trespass, resisting arrest and illegal dumping reported after Keystone XL blockade", "sentence" => "Arrested December 3, 2012 and held in Smith County Jail on a reported $65, 000 bond. The February 2013 interview reports twenty-four days served by the three defendants; the campaign also confirms release. The exact release day is unverified. This records the initial pretrial detention, not a sentence of twenty-four days."]], "isabel-indigo-brooks" => [["charges" => "Criminal trespass, resisting arrest and illegal dumping reported after Keystone XL blockade", "sentence" => "Arrested December 3, 2012 and held in Smith County Jail on a reported $65, 000 bond. The February 2013 interview reports twenty-four days served by the three defendants; the campaign also confirms release. The exact release day is unverified. This records the initial pretrial detention, not a sentence of twenty-four days."]], "camilo-pereira" => [["charges" => "Alpha headquarters protest — individual counts unverified; group reports list trespass, obstructing passage, disorderly conduct and fire-code violations", "sentence" => "Arrested June 20, 2014 and still jailed with the group at the June 23 arraignment. A July 6 campaign update confirms everyone had been released. The individual release day and final charges or disposition remain unverified; no sentence is inferred from the pretrial detention."]], "catherine-ann-macdougal" => [["charges" => "Trespassing following Coal River Mountain tree sit — no-contest plea; conspiracy dropped", "sentence" => "Entered Southern Regional Jail February 9, 2012 for seven days. The campaign recorded a jail visit and a statement during the term; the February 18 first-person update confirms that the week had been served and release had occurred. Exact release day remains unverified."], ["charges" => "Coal-barge protest at Chelyan — initial detention, individual statutory count unverified", "sentence" => "Spent two days in South Central Regional Jail immediately after the May 24, 2012 action. The November sentencing report expressly credits these two days against the later twenty-day sentence. The exact release day is unverified."], ["charges" => "Coal-barge protest plea — remaining custody after credit; individual count unverified", "sentence" => "Returned to South Central Regional Jail November 5, 2012 to serve eighteen remaining days of a twenty-day sentence, with two earlier days credited. MacDougal rejected a proposed probation arrangement; the final agreement imposed no probation. November 20 reporting confirms continued custody, and the November 26 update confirms release. The projected Thanksgiving release is not recorded as an independently verified exact day."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch313."); }

}
if (($payload["expected_case_count"] ?? null) !== 8 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 8) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B313-OK\n";
'
run "add-affiliation-prisoners" "B313-OK" "$ADD_CODE" || exit 1
echo "Batch 313 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
