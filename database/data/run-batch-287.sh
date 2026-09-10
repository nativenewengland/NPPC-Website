#!/usr/bin/env bash
# Batch 287: Three missing people from distinct Black Liberation Front research leads.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-287.sh --dry-run
# sudo -u www-data bash database/data/run-batch-287.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-287.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch287.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 287 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1970s,1980s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|min:1|max:3", "dates" => "present|array", "case_dates" => "present|array"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "middle_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka", "website", "gender", "race"])) { throw new \RuntimeException("Unexpected profile field."); }
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
    $expected = ["clifford-lumumba-futch" => ["name" => "Clifford “Lumumba” Futch", "affiliation" => ["Black Liberation Front"], "state" => "Pennsylvania", "era" => "1980s", "lat" => 41.34, "lng" => -75.96, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => "Black"], "keith-king-east-palo-alto" => ["name" => "Keith King", "affiliation" => ["Black Liberation Front"], "state" => "California", "era" => "1970s", "lat" => 37.47, "lng" => -122.14, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => "Black"], "renard-king-east-palo-alto" => ["name" => "Renard King", "affiliation" => ["Black Liberation Movement"], "state" => "California", "era" => "1970s", "lat" => 37.47, "lng" => -122.14, "in_custody" => false, "released" => false, "website" => null, "gender" => "Male", "race" => "Black"]];
    if (! isset($expected[$entry["key"]])) { throw new \RuntimeException("Unexpected reviewed identity."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected identity, affiliation, location, support site or custody status."); } }
    $reviewedDates = ["clifford-lumumba-futch" => [[]], "keith-king-east-palo-alto" => [["arrest_date" => ["year" => 1971, "month" => 5, "day" => 4], "sentenced_date" => ["year" => 1971, "month" => 6, "day" => 24]]], "renard-king-east-palo-alto" => [["arrest_date" => ["year" => 1971, "month" => 5, "day" => 4], "sentenced_date" => ["year" => 1971, "month" => 6, "day" => 24]]]];
    $reviewedVitals = ["clifford-lumumba-futch" => [], "keith-king-east-palo-alto" => [], "renard-king-east-palo-alto" => []];
    $reviewedCases = ["clifford-lumumba-futch" => [["charges" => "Administrative segregation and prison-rights restrictions challenged in Ford v. Beister", "sentence" => "Elijah reports fourteen years in a control unit and interprets the treatment as repression of prison organizing. The 1986 court opinion independently documents long-term restricted housing at SCI Dallas, a March 1980 placement, and claims concerning religious services, reviews and other restrictions. Officials cited security concerns. Most challenged claims were dismissed; the religious-services issue was remanded for further consideration. This record describes administrative confinement, not a new fourteen-year criminal sentence. The exact isolation endpoints and total continuous time served are not established.", "institution_id" => "718a7708-a645-4a5a-90db-69d92ced1102"]], "keith-king-east-palo-alto" => [["charges" => "May 4, 1971 confrontation with San Mateo County deputies; exact statutory counts unverified", "convicted" => "No-contest plea reported; request to withdraw denied", "sentence" => "Four months, imposed June 24, 1971. The July 2 report confirms custody. Keith’s separate overnight traffic detention is not the basis for inclusion. Full time served remains unknown."]], "renard-king-east-palo-alto" => [["charges" => "May 4, 1971 confrontation with San Mateo County deputies; exact statutory counts unverified", "convicted" => "Guilty plea reported; request to withdraw denied", "sentence" => "Five months, imposed June 24, 1971. The May 18 issue already described Renard as still jailed following the May 4 arrest; the July 2 report confirms later custody. Release and full time served are unverified."]]];
    if ($entry["case_dates"] !== $reviewedDates[$entry["key"]] || $entry["dates"] !== $reviewedVitals[$entry["key"]] || $entry["prisoner"]["cases"] !== $reviewedCases[$entry["key"]]) { throw new \RuntimeException("Unreviewed case or date."); }
    if (array_key_exists("photo", $entry)) { throw new \RuntimeException("No identified portrait approved for this batch."); }


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
echo "B287-OK\n";
'
run "add-affiliation-prisoners" "B287-OK" "$ADD_CODE" || exit 1
echo "Batch 287 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
