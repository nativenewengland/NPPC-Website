#!/usr/bin/env bash
# Batch 266: 54 researched Panther-related profiles and one earlier Bowers case.
# After merging, pull main and apply earlier pending batches in order.
# sudo -u www-data bash database/data/run-batch-266.sh --dry-run
# sudo -u www-data bash database/data/run-batch-266.sh
# Existing profile fields and biographies are never modified.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-266.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch266.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 266 || ($payload["expected_count"] ?? null) !== 54 || count($payload["entries"] ?? []) !== 54 || ($payload["expected_case_additions"] ?? null) !== 1 || count($payload["case_additions"] ?? []) !== 1) {
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
    if (array_diff(array_keys($case), ["charges", "sentence", "convicted"])) { throw new \RuntimeException("Unexpected case field."); }
    Validator::make($case, ["charges" => "required|string|max:255", "sentence" => "required|string", "convicted" => "sometimes|string|max:255"])->validate();
};
$assets = [];
$disk = Storage::disk("public");
$seen = [];
$seenNames = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, ["key" => "required|string", "match_names" => "required|array|min:1", "match_names.*" => "required|string", "prisoner.name" => "required|string|max:255", "prisoner.first_name" => "required|string|max:255", "prisoner.last_name" => "required|string|max:255", "prisoner.description" => "required|string", "prisoner.state" => "required|string", "prisoner.era" => "required|in:1960s,1970s", "prisoner.in_custody" => "required|boolean|declined", "prisoner.released" => "required|boolean", "prisoner.lat" => "required|numeric|between:-90,90", "prisoner.lng" => "required|numeric|between:-180,180", "prisoner.cases" => "required|array|size:1", "dates" => "present|array", "case_dates" => "present|array", "photo.file" => ["required_with:photo", "string", "regex:/^[a-z0-9-]+[.](png|jpg)$/"], "photo.sha256" => "required_with:photo|string|regex:/^[a-f0-9]{64}$/"])->validate();
    if (array_diff(array_keys($entry["prisoner"]), ["name", "first_name", "last_name", "description", "state", "era", "affiliation", "in_custody", "released", "lat", "lng", "cases", "aka"])) { throw new \RuntimeException("Unexpected profile field."); }
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
    $checkDates($entry["case_dates"], ["arrest_date", "incarceration_date", "release_date", "sentenced_date"]);
    if (isset($entry["dates"]["birthdate"], $entry["dates"]["death_date"]) && $entry["dates"]["birthdate"]["year"] > $entry["dates"]["death_date"]["year"]) { throw new \RuntimeException("Death precedes birth."); }
    $checkCase($entry["prisoner"]["cases"][0]);
    $checkSources($entry["source_ids"] ?? []);
    if (isset($entry["photo"])) {
        $photo = $entry["photo"];
        $bytes = File::get(base_path("database/data/photos/".$photo["file"]));
        $size = getimagesizefromstring($bytes);
        if (! $size || ! in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || hash("sha256", $bytes) !== $photo["sha256"]) { throw new \RuntimeException("Invalid photo: ".$photo["file"]); }
        $path = "prisoners/".$photo["file"];
        if ($disk->exists($path) && hash("sha256", $disk->get($path)) !== $photo["sha256"]) { throw new \RuntimeException("Conflicting destination photo: ".$path); }
        $assets[$entry["key"]] = ["path" => $path, "bytes" => $bytes];
    }
}
foreach ($payload["case_additions"] as $entry) {
    $checkCase($entry["case"]);
    $checkDates($entry["case_dates"], ["arrest_date"]);
    $checkSources($entry["source_ids"] ?? []);
    if ($entry["expected_slug"] !== "veronza-bowers" || $entry["expected_name"] !== "Veronza Bowers" || $entry["case_dates"] !== ["arrest_date" => ["year" => 1970, "month" => 2, "day" => 9]]) { throw new \RuntimeException("Unexpected existing-profile case target."); }
}
$result = DB::transaction(function () use ($payload, $normalize, $dryRun, $disk, $assets) {
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
    $casePlans = [];
    foreach ($payload["case_additions"] as $entry) {
        $person = $records->firstWhere("id", $entry["prisoner_id"]);
        if (! $person || $person->name !== $entry["expected_name"] || $person->slug !== $entry["expected_slug"] || $person->under_review) { throw new \RuntimeException("Existing profile identity changed."); }
        $sameDate = $person->cases()->whereDate("arrest_date", "1970-02-09")->get();
        if ($sameDate->count() > 1 || ($sameDate->count() === 1 && ($sameDate->first()->charges !== $entry["case"]["charges"] || $sameDate->first()->datePrecisionFor("arrest_date") !== "day"))) { throw new \RuntimeException("Review potentially overlapping existing Bowers case."); }
        if ($sameDate->isEmpty()) { $casePlans[] = [$person, $entry]; }
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
        if (isset($assets[$entry["key"]])) {
            $asset = $assets[$entry["key"]];
            if (! $disk->exists($asset["path"]) && ! $disk->put($asset["path"], $asset["bytes"], "public")) { throw new \RuntimeException("Photo copy failed."); }
            if (hash("sha256", $disk->get($asset["path"])) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Photo copy checksum mismatch."); }
            $record->photo = $asset["path"];
        }
        $record->save();
        $case = new PrisonerCase($cases[0]);
        $case->prisoner_id = $record->id;
        foreach ($entry["case_dates"] as $field => $parts) { $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null); }
        $case->save();
    }
    foreach ($casePlans as [$person, $entry]) {
        echo ($dryRun ? "Would add earlier case: " : "Adding earlier case: "), $person->name, "\n";
        if ($dryRun) { continue; }
        $case = new PrisonerCase($entry["case"]);
        $case->prisoner_id = $person->id;
        $case->setPartialDate("arrest_date", 1970, 2, 9);
        $case->save();
    }
    return [count($missing), count($casePlans), $preserved];
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo ($dryRun ? "Would add profiles: " : "Added profiles: "), $result[0], "; earlier cases: ", $result[1], "; existing profiles preserved: ", $result[2], "\n";
echo "B266-OK\n";
'
run "add-chapter-researched-panther-prisoners" "B266-OK" "$ADD_CODE" || exit 1
echo "Batch 266 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
