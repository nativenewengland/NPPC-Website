#!/usr/bin/env bash
# Batch 333: Five verified historical US detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-333.sh --dry-run
# sudo -u www-data bash database/data/run-batch-333.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-333.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch333.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 333 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 333."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1980s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["olive-mckeon" => ["name" => "Olive McKeon", "first_name" => "Olive", "middle_name" => null, "last_name" => "McKeon", "aka" => "Olive McCabe", "description" => "Olive McKeon was an Irish-American activist jailed for refusing a federal grand jury demand during an investigation of arms supplies to the IRA. She also campaigned for her husband Bernard during his later imprisonment.", "affiliation" => ["Irish republican movement"], "state" => "New York", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => "Female", "race" => null, "inmate_number" => null], "bernard-j-mckeon" => ["name" => "Bernard J. McKeon", "first_name" => "Bernard", "middle_name" => "J.", "last_name" => "McKeon", "aka" => "Bernard McKeon; Barney McKeon; Barney J. McKeon", "description" => "Bernard McKeon, known as Barney, was a Leitrim-born building contractor and Irish-American activist. He was jailed for resisting a grand jury and later served a separate federal term following an arms-export conspiracy conviction.", "affiliation" => ["Irish republican movement"], "state" => "New York", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "desmond-ellis" => ["name" => "Desmond Ellis", "first_name" => "Desmond", "middle_name" => null, "last_name" => "Ellis", "aka" => "Dessie Ellis", "description" => "Desmond Ellis, also known as Dessie Ellis, was detained by US authorities after attempting to enter from Canada. He challenged his exclusion and sought political asylum while facing immigration-related criminal charges.", "affiliation" => ["Irish republican movement"], "state" => "New York", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "owen-carron" => ["name" => "Owen Carron", "first_name" => "Owen", "middle_name" => null, "last_name" => "Carron", "aka" => "Owen Gerard Carron", "description" => "Owen Carron was the Fermanagh and South Tyrone MP who succeeded Bobby Sands. He was jailed in Buffalo after attempting to enter the United States from Canada with Danny Morrison.", "affiliation" => ["Sinn Fein"], "state" => "New York", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "danny-morrison" => ["name" => "Danny Morrison", "first_name" => "Danny", "middle_name" => null, "last_name" => "Morrison", "aka" => "Daniel Morrison", "description" => "Danny Morrison was a Sinn Fein publicist and later a writer. He was jailed in Buffalo with Owen Carron after attempting to enter the United States from Canada.", "affiliation" => ["Sinn Fein"], "state" => "New York", "era" => "1980s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["olive-mckeon" => [], "bernard-j-mckeon" => ["death_date" => ["year" => 1998, "month" => 6, "day" => 30]], "desmond-ellis" => [], "owen-carron" => [], "danny-morrison" => ["birthdate" => ["year" => 1953, "month" => 1, "day" => 9]]];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["olive-mckeon" => [["incarceration_date" => ["year" => 1980, "month" => 3], "release_date" => ["year" => 1980, "month" => 5]]], "bernard-j-mckeon" => [["incarceration_date" => ["year" => 1979, "month" => 11], "release_date" => ["year" => 1980, "month" => 3, "day" => 17]], ["incarceration_date" => ["year" => 1984, "month" => 9, "day" => 21]]], "desmond-ellis" => [["arrest_date" => ["year" => 1982, "month" => 2, "day" => 6], "incarceration_date" => ["year" => 1982, "month" => 2, "day" => 6], "release_date" => ["year" => 1983, "month" => 3]]], "owen-carron" => [["arrest_date" => ["year" => 1982, "month" => 1, "day" => 21], "incarceration_date" => ["year" => 1982, "month" => 1, "day" => 21], "release_date" => ["year" => 1982, "month" => 1]]], "danny-morrison" => [["arrest_date" => ["year" => 1982, "month" => 1, "day" => 21], "incarceration_date" => ["year" => 1982, "month" => 1, "day" => 21], "release_date" => ["year" => 1982, "month" => 1]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["olive-mckeon" => ["Olive McKeon", "Olive McCabe"], "bernard-j-mckeon" => ["Bernard J. McKeon", "Bernard McKeon", "Barney McKeon", "Barney J. McKeon"], "desmond-ellis" => ["Desmond Ellis", "Dessie Ellis"], "owen-carron" => ["Owen Carron", "Owen Gerard Carron"], "danny-morrison" => ["Danny Morrison", "Daniel Morrison"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["olive-mckeon" => [["charges" => "Contempt of court for refusing grand-jury demands for handwriting samples and fingerprints", "sentence" => "Imprisoned in March 1980. Released in May after a medical crisis; a later interview describes ten weeks served. No exact release day is inferred from the May 8 crisis or the ten-week duration."]], "bernard-j-mckeon" => [["charges" => "Civil contempt for refusing to cooperate with a federal grand jury investigating arms supplies to Ireland", "sentence" => "Jailed after a November 1979 subpoena; released March 17, 1980 when the grand jury expired. The obituary describes five months, but no precise entry day or documented-month total is inferred from that rounded recollection."], ["charges" => "Conspiracy to export firearms, 18 USC 371", "sentence" => "After two mistrials, convicted on the third trial in 1983. Began a three-year prison sentence September 21, 1984. A June 1985 interview places him in federal prison at Oxford, Wisconsin; the obituary also describes Danbury and confirms completed imprisonment. Exact release day remains unresolved."]], "desmond-ellis" => [["charges" => "Immigration detention and prosecution for false passport, false statements and conspiracy to bring aliens into the US; later illegal-entry plea", "sentence" => "Held from February 6, 1982 through his deportation in March 1983. A US court confirms continuing confinement in October 1982. His later letter dates his deportation and Irish re-arrest to March 3; month precision is used for the US endpoint because the handover time is unresolved. Later Irish imprisonment is excluded."]], "owen-carron" => [["charges" => "Illegal-entry and false-statement proceedings at the US-Canada border", "sentence" => "Arrested January 21, 1982 and still held January 23. Bail and expulsion followed during January; the official briefing dates deportation to January 29. Month precision preserves the unresolved interval between bail release and removal. Subsequent probation is not counted as imprisonment."]], "danny-morrison" => [["charges" => "Illegal-entry and false-statement proceedings at the US-Canada border", "sentence" => "Arrested January 21, 1982 and still held January 23. Bail and expulsion followed during January; the official briefing dates deportation to January 29. Month precision preserves the unresolved interval between bail release and removal. Subsequent probation is not counted as imprisonment."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["olive-mckeon" => ["source_file" => "database/data/photos/olive-mckeon-irish-people-1985-b333.png", "storage_path" => "prisoners/olive-mckeon-irish-people-1985-b333.png", "sha256" => "7472f2497df1da8ec1f7cf965f5da202c67621f8763d2cde84b4c78e341c1822", "source_id" => "wives1985", "identity_evidence" => "Portrait individually captioned Olive McKeon on page16. Exact rectangular crop from embedded scan image; original retained pixels verified."], "bernard-j-mckeon" => null, "desmond-ellis" => null, "owen-carron" => null, "danny-morrison" => null];
    if (($entry["photo"] ?? null) !== $reviewedPhotos[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed portrait."); }
    if (isset($entry["photo"]) && hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }

}
if (($payload["expected_case_count"] ?? null) !== 6 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 6) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B333-OK\n";
'
run "add-affiliation-prisoners" "B333-OK" "$ADD_CODE" || exit 1
echo "Batch 333 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
