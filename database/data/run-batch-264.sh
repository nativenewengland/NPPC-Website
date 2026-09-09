#!/usr/bin/env bash
# Batch 264: fill 162 missing birth/death dates for 100 existing profiles.
# After merging, pull main and apply earlier pending batches in order.
#   sudo -u www-data bash database/data/run-batch-264.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-264.sh
# Evidence: fixes/batch264.json; audits/missing-vital-dates-batch264.md.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-264.sh [--dry-run]" >&2
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
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch264.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 264 || ($payload["research_date"] ?? null) !== "2026-09-09" || ($payload["expected_count"] ?? null) !== 100 || count($payload["entries"] ?? []) !== 100 || ($payload["expected_date_count"] ?? null) !== 162) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$seenIds = [];
$seenSlugs = [];
$dateCount = 0;
foreach ($payload["entries"] as $entry) {
    if (array_diff(array_keys($entry), ["id", "slug", "name", "identity_evidence", "dates", "sources"])) {
        throw new \RuntimeException("Unsupported payload field; birth/death dates only.");
    }
    Validator::make($entry, [
        "id" => "required|uuid",
        "slug" => "required|string|regex:/^[a-z0-9-]+$/",
        "name" => "required|string",
        "identity_evidence" => "required|string",
        "dates" => "required|array:birthdate,death_date|min:1",
        "sources" => "required|array|min:1",
        "sources.*.url" => "required|url",
        "sources.*.title" => "required|string",
        "sources.*.fields" => "required|array|min:1",
        "sources.*.fields.*" => "required|in:birthdate,death_date",
        "sources.*.supports" => "required|string",
    ])->validate();
    if (isset($seenIds[$entry["id"]]) || isset($seenSlugs[$entry["slug"]])) {
        throw new \RuntimeException("Duplicate profile in payload.");
    }
    $seenIds[$entry["id"]] = $seenSlugs[$entry["slug"]] = true;
    $supported = array_merge(...array_column($entry["sources"], "fields"));
    foreach ($entry["dates"] as $field => $parts) {
        Validator::make($parts, [
            "year" => "required|integer|between:1700,2026",
            "month" => "required|integer|between:1,12",
            "day" => "required|integer|between:1,31",
        ])->validate();
        if (array_diff(array_keys($parts), ["year", "month", "day"]) || ! checkdate($parts["month"], $parts["day"], $parts["year"]) || ! in_array($field, $supported, true)) {
            throw new \RuntimeException("Invalid or unsourced date.");
        }
        if (sprintf("%04d-%02d-%02d", $parts["year"], $parts["month"], $parts["day"]) > $payload["research_date"]) {
            throw new \RuntimeException("Date is later than the research snapshot.");
        }
        $dateCount++;
    }
}
if ($dateCount !== 162) {
    throw new \RuntimeException("Unexpected number of date fields.");
}
$result = DB::transaction(function () use ($payload, $dryRun) {
    $plans = [];
    $filledDates = 0;
    foreach ($payload["entries"] as $entry) {
        $record = Prisoner::withoutGlobalScopes()->where("slug", $entry["slug"])->lockForUpdate()->sole();
        if ($record->id !== $entry["id"] || $record->name !== $entry["name"] || $record->under_review) {
            throw new \RuntimeException("Identity or review-status mismatch: ".$entry["slug"]);
        }
        $fields = [];
        foreach ($entry["dates"] as $field => $parts) {
            $original = $record->getRawOriginal($field);
            if ($original !== null && $original !== "") {
                echo "Preserved existing ", $field, ": ", $record->name, "\n";
                continue;
            }
            $record->setPartialDate($field, $parts["year"], $parts["month"], $parts["day"]);
            $fields[] = $field;
        }
        if (! $fields) { continue; }
        if ($record->birthdate && $record->death_date && $record->death_date->lessThan($record->birthdate)) {
            throw new \RuntimeException("Death predates birth: ".$entry["slug"]);
        }
        $changes = $record->getDirty();
        if (array_diff(array_keys($changes), ["birthdate", "death_date", "date_precision"])) {
            throw new \RuntimeException("Unexpected field change.");
        }
        $plans[] = ["record" => $record, "changes" => $changes, "fields" => $fields];
        $filledDates += count($fields);
    }
    // Validate all identities and plans before the first write.
    foreach ($plans as $plan) {
        $record = $plan["record"];
        echo ($dryRun ? "Would fill dates: " : "Filling dates: "), $record->name, " [", implode(", ", $plan["fields"]), "]\n";
        if ($dryRun) { continue; }
        // Guard original dates and precision metadata against concurrent edits.
        // Query-level Eloquent update bypasses biography/custody saving hooks.
        $query = Prisoner::withoutGlobalScopes()->whereKey($record->id)
            ->where("slug", $record->slug)->where("name", $record->name);
        foreach (["birthdate", "death_date", "date_precision", "under_review"] as $field) {
            $query->where($field, $record->getRawOriginal($field));
        }
        if ($query->update($plan["changes"]) !== 1) {
            throw new \RuntimeException("Concurrent edit detected; transaction rolled back.");
        }
    }
    return [count($plans), $filledDates];
});
if (! $dryRun && $result[0] > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
}
echo ($dryRun ? "Would update rows: " : "Updated rows: "), $result[0], "\n";
echo ($dryRun ? "Would fill date fields: " : "Filled date fields: "), $result[1], "\n";
echo "B264-OK\n";
'
run "fill-missing-vital-dates" "B264-OK" "$UPDATE_CODE" || exit 1
echo "Batch 264 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
