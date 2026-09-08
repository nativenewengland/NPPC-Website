#!/usr/bin/env bash
# Batch 251: add the verified 1966 mural case to Omali Yeshitela; no biography edits.
# After git pull and earlier pending batches, including 250:
#   bash database/data/run-batch-251.sh --dry-run
#   bash database/data/run-batch-251.sh
# Provenance: fixes/batch251.json.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-251.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch251.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 251 || ($payload["prisoner"] ?? []) !== ["slug" => "omali-yeshitela", "name" => "Omali Yeshitela"]) {
    throw new \RuntimeException("Unexpected batch identity.");
}
$fields = $payload["case"] ?? [];
if (count($fields) !== 4 || array_diff(array_keys($fields), ["charges", "arrest_date", "convicted", "sentence"])) {
    throw new \RuntimeException("Unsupported case fields.");
}
Validator::make($fields, [
    "charges" => "required|string|max:255",
    "arrest_date" => "required|date_format:Y-m-d|in:1966-12-29",
    "convicted" => "required|string|max:255",
    "sentence" => "required|string",
])->validate();
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$changed = DB::transaction(function () use ($payload, $fields, $dryRun) {
    $prisoner = Prisoner::withoutGlobalScopes()->where("slug", $payload["prisoner"]["slug"])->lockForUpdate()->sole();
    if ($prisoner->name !== $payload["prisoner"]["name"]) {
        throw new \RuntimeException("Prisoner identity mismatch.");
    }
    // Match broadly to avoid duplicating an independently entered historical case.
    $matches = $prisoner->cases()->lockForUpdate()->get()->filter(function ($case) {
        return ($case->arrest_date && $case->arrest_date->year === 1966)
            || preg_match("/mural|city hall|grand[ -]larceny/i", ($case->charges ?? "")." ".($case->sentence ?? ""));
    });
    if ($matches->count() > 1) {
        throw new \RuntimeException("Multiple possible mural cases; review before applying.");
    }
    if ($matches->isNotEmpty()) {
        $existing = $matches->first();
        foreach ($fields as $field => $value) {
            $actual = $field === "arrest_date" ? $existing->partialDateIso($field) : $existing->{$field};
            if ($actual !== $value) {
                throw new \RuntimeException("Existing historical case differs; preserved for review: ".$existing->id);
            }
        }
        echo "Mural case already present; preserved unchanged.\n";
        return false;
    }
    echo ($dryRun ? "Would add: " : "Adding: "), $prisoner->name, " / ", $fields["charges"], "\n";
    if ($dryRun) { return false; }
    $case = $prisoner->cases()->create(array_merge($fields, ["date_precision" => ["arrest_date" => "day"]]));
    if ($case->imprisoned_for_days !== null || $case->imprisoned_for_months !== null || $case->incarceration_date !== null || $case->release_date !== null) {
        throw new \RuntimeException("Unexpected inferred custody dates or duration; rolling back.");
    }
    return true;
});
if ($changed) { Cache::forget(PrisonerApiController::cacheKey()); }
echo "Added cases: ", ($changed ? 1 : 0), "\n";
echo "B251-OK\n";
'
run add-omali-mural-case B251-OK "$UPDATE_CODE" || exit 1
