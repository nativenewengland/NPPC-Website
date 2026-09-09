#!/usr/bin/env bash
# Batch 265: source-verified case dates for 14 existing profiles.
# After merging, pull main and apply earlier pending batches in order.
#   sudo -u www-data bash database/data/run-batch-265.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-265.sh
# Evidence: fixes/batch265.json; audits/verified-case-dates-batch265.md.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-265.sh [--dry-run]" >&2
    exit 2
fi

# Service accounts may have an unwritable home (www-data uses /var/www).
# Keep PsySH configuration and runtime files in application-owned storage.
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

UPDATE_CODE='
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch265.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 265 || ($payload["research_date"] ?? null) !== "2026-09-09" || ($payload["expected_count"] ?? null) !== 27 || count($payload["entries"] ?? []) !== 27 || ($payload["expected_people"] ?? null) !== 14 || ($payload["expected_date_count"] ?? null) !== 87 || ($payload["expected_text_count"] ?? null) !== 8) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$allowedDates = ["arrest_date", "incarceration_date", "sentenced_date", "release_date", "death_in_custody_date"];
$seen = $people = [];
$dateCount = $textCount = 0;
$checkSources = function ($refs) use ($payload) {
    if (! is_array($refs) || ! count($refs)) { throw new \RuntimeException("Missing field evidence."); }
    foreach ($refs as $ref) {
        $source = $payload["sources"][$ref] ?? null;
        if (! is_array($source) || ! filter_var($source["url"] ?? "", FILTER_VALIDATE_URL) || empty($source["title"]) || empty($source["supports"])) {
            throw new \RuntimeException("Unknown or incomplete source.");
        }
    }
};
foreach ($payload["entries"] as $entry) {
    if (array_diff(array_keys($entry), ["id", "prisoner", "expected", "dates", "text_edits"])) { throw new \RuntimeException("Unsupported payload field."); }
    Validator::make($entry, [
        "id" => "required|uuid", "prisoner" => "required|array:id,slug,name",
        "prisoner.id" => "required|uuid", "prisoner.slug" => "required|string", "prisoner.name" => "required|string",
        "expected" => "required|array", "dates" => "present|array", "text_edits" => "present|array",
    ])->validate();
    if (isset($seen[$entry["id"]]) || $entry["expected"]["id"] !== $entry["id"] || $entry["expected"]["prisoner_id"] !== $entry["prisoner"]["id"]) { throw new \RuntimeException("Duplicate or mismatched case identity."); }
    $seen[$entry["id"]] = true;
    $people[$entry["prisoner"]["id"]] = true;
    foreach ($entry["dates"] as $field => $change) {
        if (! in_array($field, $allowedDates, true) || array_diff(array_keys($change), ["value", "sources"])) { throw new \RuntimeException("Unsupported date field."); }
        $value = $change["value"] ?? "";
        if (! is_string($value) || ! preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $value, $parts) || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) || $value > $payload["research_date"]) { throw new \RuntimeException("Invalid case date."); }
        $checkSources($change["sources"] ?? null);
        $dateCount++;
    }
    foreach ($entry["text_edits"] as $edit) {
        if (array_diff(array_keys($edit), ["field", "old", "new", "sources"]) || ($edit["field"] ?? null) !== "sentence" || ! is_string($edit["old"] ?? null) || ! is_string($edit["new"] ?? null) || $edit["old"] === "" || $edit["new"] === "" || $edit["old"] === $edit["new"]) { throw new \RuntimeException("Unsupported case text edit."); }
        $checkSources($edit["sources"] ?? null);
        $textCount++;
    }
}
if (count($people) !== 14 || $dateCount !== 87 || $textCount !== 8) { throw new \RuntimeException("Unexpected correction counts."); }

$result = DB::transaction(function () use ($payload, $dryRun, $allowedDates) {
    $plans = [];
    $changedDates = $changedTexts = 0;
    foreach ($payload["entries"] as $entry) {
        $identity = $entry["prisoner"];
        $prisoner = Prisoner::withoutGlobalScopes()->whereKey($identity["id"])->lockForUpdate()->sole();
        if ($prisoner->slug !== $identity["slug"] || $prisoner->name !== $identity["name"] || $prisoner->under_review) { throw new \RuntimeException("Profile identity or review mismatch: ".$identity["slug"]); }
        $record = PrisonerCase::withoutGlobalScopes()->whereKey($entry["id"])->lockForUpdate()->sole();
        if ($record->prisoner_id !== $prisoner->id) { throw new \RuntimeException("Case belongs to another profile."); }
        $original = $record->getRawOriginal();
        $fields = [];
        foreach ($entry["dates"] as $field => $change) {
            if ($record->partialDateIso($field) === $change["value"]) { continue; }
            [$year, $month, $day] = array_map("intval", explode("-", $change["value"]));
            $record->setPartialDate($field, $year, $month, $day);
            $fields[] = $field;
        }
        $texts = 0;
        foreach ($entry["text_edits"] as $edit) {
            $value = $record->sentence ?? "";
            if (substr_count($value, $edit["new"]) === 1 && ! str_contains($value, $edit["old"])) { continue; }
            if (substr_count($value, $edit["old"]) !== 1) { throw new \RuntimeException("Case date phrase changed: ".$entry["id"]); }
            $record->sentence = str_replace($edit["old"], $edit["new"], $value);
            $texts++;
        }
        if (! $fields && ! $texts) { echo "Already correct: ", $prisoner->name, " / ", $record->id, "\n"; continue; }
        // Require the entire researched case snapshot, including existing prose.
        if (array_diff(array_keys($original), array_keys($entry["expected"])) || array_diff(array_keys($entry["expected"]), array_keys($original))) { throw new \RuntimeException("Case schema changed; review required."); }
        foreach ($original as $field => $value) {
            if ($value !== $entry["expected"][$field]) { throw new \RuntimeException("Case changed since research: ".$entry["id"]." / ".$field); }
        }
        if ($record->incarceration_date && $record->release_date && $record->release_date->lt($record->incarceration_date)) { throw new \RuntimeException("Release predates incarceration."); }
        if ($record->arrest_date && $record->incarceration_date && $record->incarceration_date->lt($record->arrest_date)) { throw new \RuntimeException("Incarceration predates arrest."); }
        if ($record->death_in_custody_date && (! $record->release_date || ! $record->death_in_custody_date->eq($record->release_date))) { throw new \RuntimeException("Death in custody must agree with custody endpoint."); }
        if (array_intersect($fields, ["incarceration_date", "release_date", "death_in_custody_date"])) {
            $record->imprisoned_for_days = $record->computeImprisonedForDays();
        }
        $changes = $record->getDirty();
        if (array_diff(array_keys($changes), array_merge($allowedDates, ["date_precision", "imprisoned_for_days", "sentence"]))) { throw new \RuntimeException("Unexpected non-case-date change."); }
        $plans[] = ["record" => $record, "original" => $original, "changes" => $changes, "name" => $prisoner->name, "fields" => $fields, "texts" => $texts];
        $changedDates += count($fields);
        $changedTexts += $texts;
    }
    // All rows are planned and checked before any write. Bypass model saving
    // hooks so date corrections cannot derive exile or alter profile flags.
    foreach ($plans as $plan) {
        echo ($dryRun ? "Would correct: " : "Correcting: "), $plan["name"], " / ", $plan["record"]->id, " [", implode(", ", $plan["fields"]), "]", ($plan["texts"] ? " + case date phrase" : ""), "\n";
        if ($dryRun) { continue; }
        $query = DB::table("prisoner_cases");
        foreach ($plan["original"] as $field => $value) { $query->where($field, $value); }
        $changes = $plan["changes"];
        $changes["updated_at"] = now()->format("Y-m-d H:i:s");
        if ($query->update($changes) !== 1) { throw new \RuntimeException("Concurrent edit detected; batch rolled back."); }
    }
    return [count($plans), $changedDates, $changedTexts];
});
// Also clear on a successful replay, allowing recovery after a cache failure.
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
    Cache::forget("tracker:payload:v2:".date("Y"));
}
echo ($dryRun ? "Would update cases: " : "Updated cases: "), $result[0], "\n";
echo "Date fields: ", $result[1], "; case date phrases: ", $result[2], "\n";
echo "B265-OK\n";
'
run "correct-verified-case-dates" "B265-OK" "$UPDATE_CODE" || exit 1
echo "Batch 265 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
