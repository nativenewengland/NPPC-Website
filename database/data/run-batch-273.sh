#!/usr/bin/env bash
# Batch 273: 5 missing ACMHR and Birmingham campaign activists from the affiliation audit.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-273.sh --dry-run
# sudo -u www-data bash database/data/run-batch-273.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-273.sh [--dry-run]" >&2
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
use App\Models\Institution;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch273.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 273 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
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
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1960s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|size:1", "dates" => "present|array", "case_dates" => "present|array"])->validate();
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
    $checkDates($entry["case_dates"], ["arrest_date", "incarceration_date", "release_date", "sentenced_date", "death_in_custody_date"]);
    if (isset($entry["dates"]["birthdate"], $entry["dates"]["death_date"]) && $entry["dates"]["birthdate"]["year"] > $entry["dates"]["death_date"]["year"]) { throw new \RuntimeException("Death precedes birth."); }
    $checkCase($entry["prisoner"]["cases"][0]);
    $checkSources($entry["source_ids"] ?? []);
    if (! empty($entry["prisoner"]["cases"][0]["institution_id"]) && ! isset($payload["institutions"][$entry["prisoner"]["cases"][0]["institution_id"]])) { throw new \RuntimeException("Unverified institution."); }
    if (empty($entry["custody_evidence"])) { throw new \RuntimeException("Missing actual custody evidence."); }
    $expected = ["abraham-lincoln-woods-jr" => ["name" => "Abraham Lincoln Woods Jr.", "affiliation" => ["Alabama Christian Movement for Human Rights", "Southern Christian Leadership Conference (SCLC)"], "state" => "Alabama", "era" => "1960s", "lat" => 33.52, "lng" => -86.8], "charles-avery-jr" => ["name" => "Charles Avery Jr.", "affiliation" => ["Birmingham civil rights campaign"], "state" => "Alabama", "era" => "1960s", "lat" => 33.52, "lng" => -86.8], "janice-wesley-kelsey" => ["name" => "Janice Wesley Kelsey", "affiliation" => ["Birmingham civil rights campaign"], "state" => "Alabama", "era" => "1960s", "lat" => 33.52, "lng" => -86.8], "freeman-a-hrabowski-iii" => ["name" => "Freeman A. Hrabowski III", "affiliation" => ["Birmingham civil rights campaign"], "state" => "Alabama", "era" => "1960s", "lat" => 33.52, "lng" => -86.8], "mamie-king-chalmers" => ["name" => "Mamie King-Chalmers", "affiliation" => ["Birmingham civil rights campaign"], "state" => "Alabama", "era" => "1960s", "lat" => 33.52, "lng" => -86.8]];
    if (! isset($expected[$entry["key"]]) || ! $entry["prisoner"]["released"]) { throw new \RuntimeException("Unexpected reviewed identity or custody status."); }
    foreach ($expected[$entry["key"]] as $field => $value) { if (($entry["prisoner"][$field] ?? null) !== $value) { throw new \RuntimeException("Unexpected affiliation or location."); } }
    $approvedDates = ["abraham-lincoln-woods-jr" => ["arrest_date" => ["year" => 1963]], "charles-avery-jr" => ["arrest_date" => ["year" => 1963, "month" => 5, "day" => 7]], "janice-wesley-kelsey" => ["arrest_date" => ["year" => 1963, "month" => 5, "day" => 2]], "freeman-a-hrabowski-iii" => ["arrest_date" => ["year" => 1963, "month" => 5]], "mamie-king-chalmers" => ["arrest_date" => ["year" => 1963]]];
    if ($entry["case_dates"] !== $approvedDates[$entry["key"]]) { throw new \RuntimeException("Unverified case date."); }

}
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
        $record = new Prisoner($fields);
        $record->sort_order = $nextOrder++;
        foreach ($entry["dates"] as $field => $parts) { $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        $record->save();
        $case = new PrisonerCase($cases[0]);
        $case->prisoner_id = $record->id;
        foreach ($entry["case_dates"] as $field => $parts) { $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        $case->save();
    }
    return [count($missing), $preserved];
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo ($dryRun ? "Would add profiles: " : "Added profiles: "), $result[0], "; existing profiles preserved: ", $result[1], "\n";
echo "B273-OK\n";
'
run "add-affiliation-prisoners" "B273-OK" "$ADD_CODE" || exit 1
echo "Batch 273 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
