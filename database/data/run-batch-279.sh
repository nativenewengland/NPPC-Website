#!/usr/bin/env bash
# Batch 279: 4 missing animal-rights grand-jury resisters from the affiliation audit.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-279.sh --dry-run
# sudo -u www-data bash database/data/run-batch-279.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-279.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch279.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 279 || ($payload["expected_count"] ?? null) !== 4 || count($payload["entries"] ?? []) !== 4) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1990s,2000s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka"])) { throw new \RuntimeException("Unexpected profile field."); }
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
    $expected = ["kimberly-jeannine-trimiew" => ["name" => "Kimberly Jeannine Trimiew", "affiliation" => ["Animal Liberation Movement", "Coalition Against Fur Farms"], "state" => "Washington", "era" => "1990s", "lat" => 47.66, "lng" => -117.43], "deborah-stout" => ["name" => "Deborah Stout", "affiliation" => ["Animal Liberation Movement", "Coalition Against Fur Farms"], "state" => "Washington", "era" => "1990s", "lat" => 47.66, "lng" => -117.43], "gina-lynn" => ["name" => "Gina Lynn", "affiliation" => ["Animal Liberation Movement", "Animal Rights Direct Action Coalition"], "state" => "California", "era" => "1990s", "lat" => 37.13, "lng" => -122.12], "kim-berardi" => ["name" => "Kim Berardi", "affiliation" => ["Animal Liberation Movement"], "state" => "Washington", "era" => "2000s", "lat" => 47.44, "lng" => -122.28]];
    if (! isset($expected[$entry["key"]]) || ! $entry["prisoner"]["released"]) { throw new \RuntimeException("Unexpected reviewed identity or custody status."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected affiliation or location."); } }
    $reviewedDates = ["kimberly-jeannine-trimiew" => [["release_date" => ["year" => 1993, "month" => 10, "day" => 20]], ["incarceration_date" => ["year" => 1994, "month" => 2, "day" => 18]]], "deborah-stout" => [["incarceration_date" => ["year" => 1994, "month" => 2, "day" => 18]]], "gina-lynn" => [["arrest_date" => ["year" => 1999, "month" => 9, "day" => 10], "incarceration_date" => ["year" => 1999, "month" => 9, "day" => 10], "release_date" => ["year" => 1999, "month" => 9, "day" => 13]], ["incarceration_date" => ["year" => 1999, "month" => 10, "day" => 5]], ["incarceration_date" => ["year" => 2004, "month" => 8, "day" => 26]]], "kim-berardi" => [["incarceration_date" => ["year" => 2004, "month" => 7]]]];
    $reviewedCases = ["kimberly-jeannine-trimiew" => [["charges" => "Civil contempt for refusing grand-jury testimony", "sentence" => "About two weeks in custody in October 1993; released on bail October 20. The contempt order was subsequently affirmed November 12, 1993."], ["charges" => "Civil contempt for refusing grand-jury testimony after immunity", "sentence" => "Confined beginning February 18, 1994, and still jailed in June reporting. The Ninth Circuit affirmed the contempt order March 28. Exact release date unverified."]], "deborah-stout" => [["charges" => "Civil contempt for refusing grand-jury testimony after immunity", "sentence" => "Jailed February 18, 1994; continued confinement was reported in June. Her contempt order was affirmed March 28. The reviewed sources do not establish her release date."]], "gina-lynn" => [["charges" => "Contempt warrant for declining a federal grand-jury appearance", "sentence" => "Held in an Oakland jail over the weekend, September 10–13, 1999; released on a promise to attend proceedings in Missouri."], ["charges" => "Contempt for refusing to cooperate with a federal grand jury", "sentence" => "Taken into custody October 5, 1999, in Missouri; her account was published while she was held at St. Louis County Jail in Clayton. Release date unverified."], ["charges" => "Civil contempt for refusing federal grand-jury testimony", "sentence" => "Confined at SeaTac beginning August 26, 2004. Released in September after about three weeks when the judge concluded that continued confinement would not compel testimony.", "institution_id" => "4a4b11de-9d1f-4563-b105-418cf3dfc8ad"]], "kim-berardi" => [["charges" => "Civil contempt for refusing federal grand-jury testimony", "sentence" => "Several days in custody at SeaTac in July 2004; exact custody dates remain unverified.", "institution_id" => "4a4b11de-9d1f-4563-b105-418cf3dfc8ad"]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== [] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    $approvedPhotos = ["gina-lynn" => ["source_file" => "database/data/photos/gina-lynn-lexxie-earth-first.png", "storage_path" => "prisoners/gina-lynn-lexxie-earth-first.png", "sha256" => "e5e1df8f36d722b586c4d3bc96c8a2a31a4693e555ca28dba4c744f01e5c987a"]];
    $approvedPhoto = $approvedPhotos[$entry["key"]] ?? null;
    if ($approvedPhoto) {
        foreach ($approvedPhoto as $field => $value) { if (($entry["photo"][$field] ?? null) !== $value) { throw new \RuntimeException("Unverified portrait."); } }
        if (hash("sha256", File::get(base_path($approvedPhoto["source_file"]))) !== $approvedPhoto["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }
    } elseif (isset($entry["photo"])) { throw new \RuntimeException("Unreviewed portrait."); }

}
if (($payload["expected_case_count"] ?? null) !== 7 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 7) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B279-OK\n";
'
run "add-affiliation-prisoners" "B279-OK" "$ADD_CODE" || exit 1
echo "Batch 279 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
