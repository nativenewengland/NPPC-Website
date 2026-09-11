#!/usr/bin/env bash
# Batch 327: Three verified IWO political detainees; accumulate toward 100-person PR.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-327.sh --dry-run
# sudo -u www-data bash database/data/run-batch-327.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-327.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch327.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 327 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$precisionProbe = new PrisonerCase();
$precisionProbe->setPartialDate("incarceration_date", 1926, 6);
$precisionProbe->setPartialDate("release_date", 1926, 7);
if ($precisionProbe->computeImprisonedForDays() !== null) { throw new \RuntimeException("Pull the accompanying partial-date counter fix before applying batch 327."); }
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "sometimes|string", "prisoner.era" => "sometimes|in:1950s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "sometimes|numeric|between:-90,90", "prisoner.lng" => "sometimes|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $expected = ["andrew-dmytryshyn" => ["name" => "Andrew Dmytryshyn", "first_name" => "Andrew", "middle_name" => null, "last_name" => "Dmytryshyn", "aka" => "Andrew Dmytryshin", "description" => "Andrew Dmytryshyn was a leader of the Ukrainian American Fraternal Union, a section of the International Workers Order. His political associations became the subject of deportation proceedings, and he was held at Ellis Island after his bail was revoked.", "affiliation" => ["International Workers Order", "Ukrainian American Fraternal Union"], "state" => "New York", "era" => "1950s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "sam-milgrom" => ["name" => "Sam Milgrom", "first_name" => "Sam", "middle_name" => null, "last_name" => "Milgrom", "aka" => "Sam Milgram", "description" => "Sam Milgrom was the national executive secretary of the International Workers Order. During the campaign to deport immigrant political organizers, he was confined at Ellis Island and later held under guard in a hospital.", "affiliation" => ["International Workers Order"], "state" => "New York", "era" => "1950s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null], "ignatz-mezei" => ["name" => "Ignatz Mezei", "first_name" => "Ignatz", "middle_name" => null, "last_name" => "Mezei", "aka" => "Ignatz Mezi", "description" => "Ignatz Mezei was a Buffalo carpenter and officer of the International Workers Order’s Hungarian lodge. After returning from Europe, he was detained at Ellis Island as an alleged security risk. His challenge to exclusion and confinement reached the United States Supreme Court.", "affiliation" => ["International Workers Order"], "state" => "New York", "era" => "1950s", "lat" => null, "lng" => null, "in_custody" => false, "released" => true, "website" => null, "gender" => null, "race" => null, "inmate_number" => null]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected reviewed profile field."); } }
    $reviewed = ["andrew-dmytryshyn" => [], "sam-milgrom" => [], "ignatz-mezei" => []];
    if ($entry["dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed dates."); }
    $reviewed = ["andrew-dmytryshyn" => [["incarceration_date" => ["year" => 1952], "release_date" => ["year" => 1952, "month" => 11]]], "sam-milgrom" => [["incarceration_date" => ["year" => 1952]]], "ignatz-mezei" => [["arrest_date" => ["year" => 1950, "month" => 2, "day" => 9], "incarceration_date" => ["year" => 1950], "release_date" => ["year" => 1952, "month" => 5, "day" => 10]], ["incarceration_date" => ["year" => 1953, "month" => 4, "day" => 22], "release_date" => ["year" => 1954, "month" => 8]]]];
    if ($entry["case_dates"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed case_dates."); }
    $reviewed = ["andrew-dmytryshyn" => ["Andrew Dmytryshyn", "Andrew Dmytryshin"], "sam-milgrom" => ["Sam Milgrom", "Sam Milgram"], "ignatz-mezei" => ["Ignatz Mezei", "Ignatz Mezi"]];
    if ($entry["match_names"] !== $reviewed[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed match_names."); }
    $reviewedCases = ["andrew-dmytryshyn" => [["charges" => "Civil immigration detention pending political deportation proceedings", "sentence" => "Held at Ellis Island in 1952 after bail revocation. Contemporary reports disagree on the recommitment date; only the year is entered. A later report confirms parole in November 1952. No criminal prison sentence or six-month term actually served is asserted."]], "sam-milgrom" => [["charges" => "Civil immigration detention pending political deportation proceedings", "sentence" => "Recommitted after bail cancellation in 1952. He remained detained for weeks and was moved to hospital under guard because of serious illness. Hospital transfer did not end his custody. Subsequent historical research confirms that he eventually gained freedom; the exact release date remains unverified."]], "ignatz-mezei" => [["charges" => "Civil immigration exclusion on alleged security grounds; initial Ellis Island detention", "sentence" => "Arrived February 9, 1950 and was detained pending exclusion. Failed attempts to send him abroad interrupted his physical presence on Ellis Island, so no exact continuous-day total is asserted. He was released on bond May 10, 1952. This detention is separated from his later return to custody."], ["charges" => "Civil immigration exclusion on alleged security grounds; return after Supreme Court decision", "sentence" => "Returned to Ellis Island April 22, 1953 after the Supreme Court reversed the bond ruling. Released on immigration parole in August 1954 and returned to Buffalo. The intervening period at home on bond is excluded; no criminal sentence is assigned."]]];
    if ($entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unexpected reviewed cases."); }
    $reviewedPhotos = ["andrew-dmytryshyn" => ["source_file" => "database/data/photos/andrew-dmytryshyn-national-guardian-1950-b327.png", "storage_path" => "prisoners/andrew-dmytryshyn-national-guardian-1950-b327.png", "sha256" => "7549718f1a0d0fc09d26a5acd62a48e2fb6688f55da98e84aa9c83c746528547", "crop" => [2250, 2900, 3075, 3720], "source_id" => "guardian1950", "identity_evidence" => "Single portrait captioned ANDREW DMYTRYSHYN below the photograph in the July26,1950 National Guardian p.4 article about this IWO officer."], "sam-milgrom" => null, "ignatz-mezei" => null];
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
echo "B327-OK\n";
'
run "add-affiliation-prisoners" "B327-OK" "$ADD_CODE" || exit 1
echo "Batch 327 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
