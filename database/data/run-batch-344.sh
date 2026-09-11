#!/usr/bin/env bash
# Batch 344: Three verified Mississippi organizers; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-344.sh --dry-run
# sudo -u www-data bash database/data/run-batch-344.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-344.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch344.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 344 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 344."); }
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
    $expected = ["mary-lane" => ["name" => "Mary Lane", "first_name" => "Mary", "middle_name" => null, "last_name" => "Lane", "aka" => null, "description" => "Mary Lane organized voter registration in Greenwood and served as a SNCC project director. She spent 48 days imprisoned at Parchman in 1963 and later represented the Mississippi Freedom Democratic Party at the 1964 Democratic National Convention.", "affiliation" => ["Student Nonviolent Coordinating Committee", "Mississippi Freedom Democratic Party"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "douglas-macarthur-cotton" => ["name" => "Douglas MacArthur Cotton", "first_name" => "Douglas", "middle_name" => "MacArthur", "last_name" => "Cotton", "aka" => "MacArthur Cotton; Mac Cotton", "description" => "Douglas MacArthur Cotton was a Mississippi Freedom Rider and SNCC voter-registration organizer. His imprisonment included a Freedom Ride case and a later Greenwood prosecution that sent him to the county farm and Parchman. His sworn account also documents a separate 1964 detention in Natchez.", "affiliation" => ["Student Nonviolent Coordinating Committee", "Congress of Racial Equality", "Freedom Riders"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "george-greene" => ["name" => "George Greene", "first_name" => "George", "middle_name" => null, "last_name" => "Greene", "aka" => null, "description" => "George Greene was a Greenwood civil-rights organizer who worked with SNCC and the NAACP youth movement. His 1963 imprisonment included the Leflore County Penal Farm and Parchman. He continued voter-registration work after release.", "affiliation" => ["Student Nonviolent Coordinating Committee", "NAACP"], "state" => "Mississippi", "era" => "1960s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["mary-lane" => ["birthdate" => ["year" => 1939, "month" => 12, "day" => 16], "death_date" => ["year" => 2015, "month" => 2, "day" => 15]], "douglas-macarthur-cotton" => ["birthdate" => ["year" => 1942, "month" => 4, "day" => 23]], "george-greene" => ["birthdate" => ["year" => 1943, "month" => 10, "day" => 8], "death_date" => ["year" => 2018, "month" => 10, "day" => 24]]];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["mary-lane" => [["incarceration_date" => ["year" => 1963], "release_date" => ["year" => 1963]]], "douglas-macarthur-cotton" => [["arrest_date" => ["year" => 1961], "incarceration_date" => ["year" => 1961]], ["arrest_date" => ["year" => 1963, "month" => 6, "day" => 25], "incarceration_date" => ["year" => 1963, "month" => 6, "day" => 25], "sentenced_date" => ["year" => 1963, "month" => 6, "day" => 25], "release_date" => ["year" => 1963, "month" => 8]], ["arrest_date" => ["year" => 1964, "month" => 2], "incarceration_date" => ["year" => 1964, "month" => 2], "release_date" => ["year" => 1964]]], "george-greene" => [["incarceration_date" => ["year" => 1963], "release_date" => ["year" => 1963]], ["arrest_date" => ["year" => 1964, "month" => 2], "incarceration_date" => ["year" => 1964, "month" => 2], "release_date" => ["year" => 1964]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["mary-lane" => ["Mary Lane"], "douglas-macarthur-cotton" => ["Douglas MacArthur Cotton", "MacArthur Cotton", "Mac Cotton"], "george-greene" => ["George Greene"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["mary-lane" => [["charges" => "Imprisonment following Greenwood civil-rights organizing; individual statutory charge unverified", "sentence" => "Forty-eight days actually spent at Parchman in 1963. Exact custody endpoints and imposed sentence remain unverified."]], "douglas-macarthur-cotton" => [["charges" => "Freedom Ride arrest after attempting to buy a ticket at the segregated bus station", "sentence" => "Thirty-nine days on death row are documented. Arrest and imprisonment began in 1961; exact endpoints and sentence unverified. Placement on death row does not imply a death sentence."], ["charges" => "Disturbing the peace during Greenwood voter registration", "sentence" => "Convicted June 25, 1963; four months hard labor and $200 fine. His affidavit records 55 days at Parchman after county-farm confinement. Released on bond in August 1963; exact day unverified."], ["charges" => "Initially held for auto-theft investigation; subsequently charged with vagrancy", "sentence" => "Thirty hours detained with George Greene during voter-registration work in Natchez in late February 1964. Exact days and disposition unverified."]], "george-greene" => [["charges" => "Arrest during a Greenwood demonstration; exact statutory charge unverified", "sentence" => "Six-month hard-labor sentence and $500 fine; the interview-based history reports 67 days actually served before his father obtained release. Custody included county farm and Parchman; exact endpoints unverified."], ["charges" => "Initially held for auto-theft investigation; subsequently charged with speeding", "sentence" => "Thirty hours detained with MacArthur Cotton during voter-registration work in Natchez in late February 1964. Exact days and disposition unverified."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["mary-lane" => null, "douglas-macarthur-cotton" => ["source_file" => "database/data/photos/douglas-macarthur-cotton-chris-young-b344.png", "storage_path" => "prisoners/douglas-macarthur-cotton-chris-young-b344.png", "sha256" => "f514b5c8712c504f7980336a644e1e77ce88e8071e6c27e977611f00bb38e50c", "source_id" => "cotton-interview", "identity_evidence" => "Individually captioned Douglas Macarthur Mac Cotton by the interviewer/photographer Chris Young. Exact original-pixel crop; see CREDITS-batch344.md."], "george-greene" => null];
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
echo "B344-OK\n";
'
run "add-affiliation-prisoners" "B344-OK" "$ADD_CODE" || exit 1
echo "Batch 344 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
