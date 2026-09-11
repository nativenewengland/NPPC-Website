#!/usr/bin/env bash
# Batch 329: Four verified military conscientious resisters; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-329.sh --dry-run
# sudo -u www-data bash database/data/run-batch-329.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-329.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch329.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 329 || ($payload["expected_count"] ?? null) !== 4 || count($payload["entries"] ?? []) !== 4) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 329."); }
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
    $expected = ["joel-klemkewicz" => ["name" => "Joel Klemkewicz", "first_name" => "Joel", "middle_name" => null, "last_name" => "Klemkewicz", "aka" => "Joel Klenkewicz", "description" => "Joel Klemkewicz was a US Marine combat engineer whose religious objections led him to refuse armed duty. After imprisonment, he studied for the ministry and served an Adventist congregation in Okinawa.", "affiliation" => ["Seventh-day Adventists", "Conscientious objection"], "state" => null, "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "katherine-jashinski" => ["name" => "Katherine Jashinski", "first_name" => "Katherine", "middle_name" => null, "last_name" => "Jashinski", "aka" => "Kathrine Jashinski", "description" => "Katherine Jashinski was a Texas Army National Guard member who publicly sought conscientious-objector status and refused weapons training for service in Afghanistan. She was imprisoned at the Miramar brig and returned to Texas after release.", "affiliation" => ["Conscientious objection"], "state" => "California", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ivan-brobeck" => ["name" => "Ivan Brobeck", "first_name" => "Ivan", "middle_name" => null, "last_name" => "Brobeck", "aka" => null, "description" => "Ivan Brobeck was a Marine who opposed returning to Iraq after serving there in 2004. He went to Canada, later returned to the United States and was imprisoned following a military prosecution.", "affiliation" => ["Iraq War resistance"], "state" => "Virginia", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ryan-jackson" => ["name" => "Ryan Jackson", "first_name" => "Ryan", "middle_name" => null, "last_name" => "Jackson", "aka" => null, "description" => "Ryan Jackson was a US Army private who sought release from military service on conscientious grounds before deployment to Iraq. He joined antiwar organizing and wrote publicly from the Charleston Naval Brig.", "affiliation" => ["Iraq Veterans Against the War", "Conscientious objection"], "state" => "South Carolina", "era" => "2000s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["joel-klemkewicz" => [], "katherine-jashinski" => [], "ivan-brobeck" => [], "ryan-jackson" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["joel-klemkewicz" => [["sentenced_date" => ["year" => 2004, "month" => 12]]], "katherine-jashinski" => [["sentenced_date" => ["year" => 2006, "month" => 5, "day" => 23], "incarceration_date" => ["year" => 2006, "month" => 5], "release_date" => ["year" => 2006, "month" => 7, "day" => 9]]], "ivan-brobeck" => [["incarceration_date" => ["year" => 2006], "sentenced_date" => ["year" => 2006, "month" => 12, "day" => 5], "release_date" => ["year" => 2007, "month" => 2]]], "ryan-jackson" => [["incarceration_date" => ["year" => 2008, "month" => 4, "day" => 14], "sentenced_date" => ["year" => 2008, "month" => 5, "day" => 30], "release_date" => ["year" => 2008]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["joel-klemkewicz" => ["Joel Klemkewicz", "Joel Klenkewicz"], "katherine-jashinski" => ["Katherine Jashinski", "Kathrine Jashinski"], "ivan-brobeck" => ["Ivan Brobeck"], "ryan-jackson" => ["Ryan Jackson"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["joel-klemkewicz" => [["charges" => "Military refusal of duties on religious grounds; exact court-martial specifications unresolved", "sentence" => "The resistance archive reports a December 2004 court-martial and seven-month sentence. His church later reported four months actually imprisoned for refusing Sabbath work. These accounts describe different aspects of his conscientious refusal; the exact specifications and custody endpoints remain unresolved. A later discharge upgrade is not a jail-release date.", "imprisoned_for_months" => 4]], "katherine-jashinski" => [["charges" => "Disobeying a superior officer; acquitted of missing movement by design", "sentence" => "Sentenced May 23, 2006 to 120 days, with credit for pretrial confinement, and sent to military prison near San Diego. A reporter visited her at Miramar and confirmed her release on July 9, 2006. The reported 47 days remaining after credit are not the full sentence or a separately added period. The earlier Fort Benning restriction is not given invented prison endpoints."]], "ivan-brobeck" => [["charges" => "Unauthorized absence and missing military movement", "sentence" => "Returned to US authorities in November 2006. Court-martialed December 5, 2006; Ann Wright reports an eight-month sentence and actual release in February 2007. The support archive confirms imprisonment at Quantico. Other summaries give a different sentence, so no full term or exact number of days served is entered."]], "ryan-jackson" => [["charges" => "Multiple unauthorized absences preceding conscientious-objector application", "sentence" => "Held in pretrial confinement after returning to Fort Gordon on April 14, 2008. Convicted May 30 and sentenced to 100 days with credit for custody already served. A later interview-based book confirms that he was released after the credited term. The contemporary expectation of a late-June release is not copied as an exact endpoint."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["joel-klemkewicz" => ["source_file" => "database/data/photos/joel-klemkewicz-nsd-2010-b329.png", "storage_path" => "prisoners/joel-klemkewicz-nsd-2010-b329.png", "sha256" => "dc36536411b01ef5427514e38ee6c0bbdb0c1c5625c211e01f9f5b6abc1e6152", "source_id" => "joel-church", "identity_evidence" => "Single portrait beneath the named Joel Klemkewicz article on printed page16; caption Pr. Joel Klenkewicz. Embedded image Im4 decoded from original PDF, native234x194, no generative edits or resizing."], "katherine-jashinski" => null, "ivan-brobeck" => null, "ryan-jackson" => null];
    if (($entry["photo"] ?? null) !== $reviewedPhotos[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed portrait."); }
    if (isset($entry["photo"]) && hash("sha256", File::get(base_path($entry["photo"]["source_file"]))) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Portrait checksum mismatch."); }

}
if (($payload["expected_case_count"] ?? null) !== 4 || array_sum(array_map(fn ($e) => count($e["prisoner"]["cases"]), $payload["entries"])) !== 4) { throw new \RuntimeException("Unexpected total case count."); }
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
echo "B329-OK\n";
'
run "add-affiliation-prisoners" "B329-OK" "$ADD_CODE" || exit 1
echo "Batch 329 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
