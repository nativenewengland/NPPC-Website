#!/usr/bin/env bash
# Batch 255: fill verified empty fields in six existing activist cases.
# Apply earlier pending batches first. Biographies and populated fields are preserved.
#   sudo -u www-data bash database/data/run-batch-255.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-255.sh
# Sources and limits: fixes/batch255.json; audits/missing-activist-cases-2026-09-08.md.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-255.sh [--dry-run]" >&2
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

UPDATE_CODE='
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch255.json")), true, 512, JSON_THROW_ON_ERROR);
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$textFields = ["judge", "prosecutor", "indicted", "convicted", "plead", "sentence"];
$dateFields = ["arrest_date", "incarceration_date", "sentenced_date", "in_exile_since", "end_of_exile"];
if (($payload["batch"] ?? null) !== 255 || ($payload["expected_count"] ?? null) !== 6 || count($payload["entries"] ?? []) !== 6 || ($payload["expected_field_count"] ?? null) !== 17) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$seen = [];
$fieldCount = 0;
foreach ($payload["entries"] as $entry) {
    if (array_diff(array_keys($entry), ["id", "slug", "name", "case_id", "match_charges", "fields", "dates", "sources", "notes"])) {
        throw new \RuntimeException("Unsupported entry field.");
    }
    Validator::make($entry, [
        "id" => "required|uuid", "case_id" => "required|uuid",
        "slug" => "required|string|regex:/^[a-z0-9-]+$/", "name" => "required|string",
        "match_charges" => "required|string", "fields" => "present|array", "dates" => "present|array",
        "sources" => "required|array|min:1", "sources.*.url" => "required|url",
        "sources.*.supports" => "required|array|min:1",
    ])->validate();
    if (isset($seen[$entry["id"]]) || isset($seen[$entry["case_id"]]) || isset($seen[$entry["slug"]])) {
        throw new \RuntimeException("Duplicate profile or case.");
    }
    $seen[$entry["id"]] = $seen[$entry["case_id"]] = $seen[$entry["slug"]] = true;
    if (array_diff(array_keys($entry["fields"]), $textFields) || array_diff(array_keys($entry["dates"]), $dateFields)) {
        throw new \RuntimeException("Unsupported case field; no profile or duration writes allowed.");
    }
    foreach ($entry["fields"] as $value) {
        if (! is_string($value) || trim($value) === "") {
            throw new \RuntimeException("Empty or invalid text value.");
        }
    }
    foreach ($entry["dates"] as $parts) {
        Validator::make($parts, [
            "year" => "required|integer|between:1700,2026",
            "month" => "sometimes|required|integer|between:1,12",
            "day" => "sometimes|required|integer|between:1,31",
        ])->validate();
        if (array_diff(array_keys($parts), ["year", "month", "day"]) || (isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) {
            throw new \RuntimeException("Invalid partial date.");
        }
    }
    $proposed = array_merge(array_keys($entry["fields"]), array_keys($entry["dates"]));
    $supported = array_merge(...array_column($entry["sources"], "supports"));
    if (array_diff($proposed, $supported)) {
        throw new \RuntimeException("Every proposed field needs a source.");
    }
    $fieldCount += count($proposed);
}
if ($fieldCount !== 17) { throw new \RuntimeException("Unexpected proposed field count."); }

// Mutate only an in-memory model here; this planner also supports no-write tests.
$planCase = function ($record, $entry) use ($textFields, $dateFields) {
    foreach ($entry["fields"] as $field => $value) {
        $old = $record->getRawOriginal($field);
        if ($old === null || $old === "") {
            $record->{$field} = $value;
        } else {
            echo "Preserved existing ", $entry["name"], ": ", $field, "\n";
        }
    }
    foreach ($entry["dates"] as $field => $parts) {
        $old = $record->getRawOriginal($field);
        if ($old !== null && $old !== "") {
            echo "Preserved existing ", $entry["name"], ": ", $field, "\n";
            continue;
        }
        $precision = isset($parts["day"]) ? "day" : (isset($parts["month"]) ? "month" : "year");
        $existingPrecision = $record->date_precision[$field] ?? null;
        if ($existingPrecision !== null && $existingPrecision !== $precision) {
            echo "Preserved existing precision; skipped ", $entry["name"], ": ", $field, "\n";
            continue;
        }
        $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null);
    }
    $dirty = $record->getDirty();
    if (array_diff(array_keys($dirty), array_merge($textFields, $dateFields, ["date_precision"]))) {
        throw new \RuntimeException("Unexpected model side effect.");
    }
    return $dirty;
};

$changed = DB::transaction(function () use ($payload, $dryRun, $planCase) {
    $plans = [];
    foreach ($payload["entries"] as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->where("slug", $entry["slug"])->lockForUpdate()->sole();
        if ($prisoner->id !== $entry["id"] || $prisoner->name !== $entry["name"]) {
            throw new \RuntimeException("Profile identity mismatch: ".$entry["slug"]);
        }
        $record = PrisonerCase::withoutGlobalScopes()->whereKey($entry["case_id"])->lockForUpdate()->sole();
        if ($record->prisoner_id !== $entry["id"] || $record->getRawOriginal("charges") !== $entry["match_charges"]) {
            throw new \RuntimeException("Case identity mismatch: ".$entry["slug"]);
        }
        $dirty = $planCase($record, $entry);
        if ($dirty) { $plans[] = compact("record", "entry", "dirty"); }
    }
    // Every identity is checked before any write. No save hooks or biography edits.
    foreach ($plans as $plan) {
        ["record" => $record, "entry" => $entry, "dirty" => $dirty] = $plan;
        echo ($dryRun ? "Would fill case: " : "Filling case: "), $entry["name"], " ", json_encode($dirty, JSON_UNESCAPED_UNICODE), "\n";
        if ($dryRun) { continue; }
        $query = PrisonerCase::withoutGlobalScopes()->whereKey($record->id)
            ->where("prisoner_id", $entry["id"])->where("charges", $entry["match_charges"]);
        foreach ($dirty as $field => $value) {
            // Includes original precision JSON, preserving simultaneous edits.
            $query->where($field, $record->getRawOriginal($field));
        }
        if ($query->update($dirty) !== 1) {
            throw new \RuntimeException("Concurrent case edit detected; transaction rolled back.");
        }
    }
    return count($plans);
});
if (! $dryRun && $changed > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
}
echo ($dryRun ? "Would update case rows: " : "Updated case rows: "), $changed, "\n";
echo "B255-OK\n";
'
run "fill-missing-activist-case-fields" "B255-OK" "$UPDATE_CODE" || exit 1
