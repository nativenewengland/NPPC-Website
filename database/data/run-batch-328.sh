#!/usr/bin/env bash
# Batch 328: Five verified military war resisters; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-328.sh --dry-run
# sudo -u www-data bash database/data/run-batch-328.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-328.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch328.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 328 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 328."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:2000s,2010s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["victor-agosto" => ["name" => "Victor Agosto", "first_name" => "Victor", "middle_name" => null, "last_name" => "Agosto", "aka" => "Victor Manuel Agosto", "description" => "Victor Agosto was an Army communications specialist and Iraq veteran who publicly refused deployment to Afghanistan. He participated in Iraq Veterans Against the War and the Under the Hood GI coffeehouse community at Fort Hood.", "affiliation" => ["Iraq Veterans Against the War"], "state" => "Texas", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "marc-a-hall" => ["name" => "Marc A. Hall", "first_name" => "Marc", "middle_name" => "A.", "last_name" => "Hall", "aka" => "Marc Hall; Mark Hall; Marc Watercus", "description" => "Marc Hall was an Iraq veteran and Army specialist who protested the military’s stop-loss policy through a rap song. He challenged his detention and sought conscientious-objector status while his supporters organized a legal defense.", "affiliation" => ["Iraq Veterans Against the War"], "state" => "Georgia", "era" => "2010s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ricky-clousing" => ["name" => "Ricky Clousing", "first_name" => "Ricky", "middle_name" => null, "last_name" => "Clousing", "aka" => null, "description" => "Ricky Clousing was an Army interrogator who spoke publicly against the Iraq War after returning from deployment. He left his unit, later surrendered, and was jailed following an unauthorized-absence plea.", "affiliation" => ["Iraq War resistance"], "state" => "North Carolina", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "mark-wilkerson" => ["name" => "Mark Wilkerson", "first_name" => "Mark", "middle_name" => null, "last_name" => "Wilkerson", "aka" => null, "description" => "Mark Wilkerson was an Iraq veteran who sought conscientious-objector status and refused another deployment. After serving a military prison term, he began working with Iraq Veterans Against the War.", "affiliation" => ["Iraq Veterans Against the War"], "state" => "Texas", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "agustin-aguayo" => ["name" => "Agustin Aguayo", "first_name" => "Agustin", "middle_name" => null, "last_name" => "Aguayo", "aka" => "Agustín Aguayo", "description" => "Agustin Aguayo was a US Army combat medic who sought release as a conscientious objector and refused a second deployment to Iraq. Amnesty International recognized him as a prisoner of conscience during his imprisonment by the US military.", "affiliation" => ["Iraq War resistance"], "state" => "California", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["victor-agosto" => [], "marc-a-hall" => [], "ricky-clousing" => [], "mark-wilkerson" => [], "agustin-aguayo" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["victor-agosto" => [["sentenced_date" => ["year" => 2009, "month" => 8, "day" => 5], "incarceration_date" => ["year" => 2009, "month" => 8, "day" => 5], "release_date" => ["year" => 2009, "month" => 8, "day" => 29]]], "marc-a-hall" => [["arrest_date" => ["year" => 2009, "month" => 12, "day" => 12], "incarceration_date" => ["year" => 2009, "month" => 12, "day" => 12], "release_date" => ["year" => 2010, "month" => 4]]], "ricky-clousing" => [["sentenced_date" => ["year" => 2006, "month" => 10, "day" => 12], "incarceration_date" => ["year" => 2006, "month" => 10], "release_date" => ["year" => 2006, "month" => 12]]], "mark-wilkerson" => [["sentenced_date" => ["year" => 2007, "month" => 2, "day" => 22], "incarceration_date" => ["year" => 2007], "release_date" => ["year" => 2007, "month" => 7]]], "agustin-aguayo" => [["arrest_date" => ["year" => 2006, "month" => 9, "day" => 26], "incarceration_date" => ["year" => 2006, "month" => 9, "day" => 26], "sentenced_date" => ["year" => 2007, "month" => 3, "day" => 6], "release_date" => ["year" => 2007, "month" => 4, "day" => 18]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["victor-agosto" => ["Victor Agosto", "Victor Manuel Agosto"], "marc-a-hall" => ["Marc A. Hall", "Marc Hall", "Mark Hall", "Marc Watercus"], "ricky-clousing" => ["Ricky Clousing"], "mark-wilkerson" => ["Mark Wilkerson"], "agustin-aguayo" => ["Agustin Aguayo", "Agustín Aguayo"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["victor-agosto" => [["charges" => "Disobeying a lawful order connected with deployment to Afghanistan", "sentence" => "Sentenced August 5, 2009 to thirty days in jail, reduction in rank and loss of pay. He was taken away under guard after the hearing and served in Bell County Jail. Released August 29, 2009, before the full sentence expired. Military discharge was a separate later event."]], "marc-a-hall" => [["charges" => "Communicating threats — Article 134, Uniform Code of Military Justice; allegations disputed", "sentence" => "Jailed December 12, 2009, initially in Georgia county custody. He remained at Liberty County Jail in February 2010 and was then transported under guard to continued US military confinement overseas, including Camp Arifjan, Kuwait. An April 17 update confirms he was free, with discharge pending. Exact release day and final disposition of all specifications are withheld; no conviction or completed sentence is asserted."]], "ricky-clousing" => [["charges" => "Absent without leave; plea avoided a desertion finding", "sentence" => "Sentenced October 12, 2006 to eleven months, with all but three months suspended under the plea agreement. A later support report describes actual imprisonment and his return home in December 2006. The suspended balance is excluded from time served. Exact commitment and release days are not established."]], "mark-wilkerson" => [["charges" => "Unauthorized absence and missing military movement; plea descriptions differ between reports", "sentence" => "An eyewitness reports conviction and a seven-month sentence on February 22, 2007. The support campaign confirms actual confinement at Fort Sill and reports him already free in its July 26 update. Precise entry and release days and total credited time remain unresolved; the seven-month sentence is not inserted as seven months actually served."]], "agustin-aguayo" => [["charges" => "Desertion and missing movement following refusal to redeploy to Iraq", "sentence" => "Taken into US Army custody at Fort Irwin, California on September 26, 2006. Later held in the US military prison at Mannheim, Germany, from October 3. Sentenced March 6, 2007 to eight months, with credit for pretrial detention. Amnesty confirms actual release April 18, 2007. The sentence and pretrial credit are not counted twice."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    if (isset($entry["photo"])) { throw new \RuntimeException("No portrait was verified for batch328."); }

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
echo "B328-OK\n";
'
run "add-affiliation-prisoners" "B328-OK" "$ADD_CODE" || exit 1
echo "Batch 328 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
