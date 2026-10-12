#!/usr/bin/env bash
# Batch 433: fill verified missing fields for 1950s profiles and cases.
# Provenance: fixes/batch433.json, audits/1950s-deep-dive-batch433.md,
# with no photo changes. All existing nonempty fields are guarded.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-433.sh [--dry-run]" >&2
    exit 2
fi
nppc_psysh_dir="$(pwd)/storage/framework/psysh"
if ! (umask 077; mkdir -p "$nppc_psysh_dir/config" "$nppc_psysh_dir/data" "$nppc_psysh_dir/runtime"); then
    echo "Cannot prepare PsySH storage; run as the application owner." >&2
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
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

$payload = json_decode(File::get(base_path("database/data/fixes/batch433.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 433 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$allowed = ["prisoners" => ["photo", "birthdate", "death_date", "date_precision"], "prisoner_cases" => ["arrest_date", "sentenced_date", "incarceration_date", "release_date", "date_precision"]];
$seen = [];
$assets = [];
$disk = Storage::disk("public");
foreach ($payload["entries"] as $entry) {
    $table = $entry["table"];
    $key = $table."/".$entry["id"];
    if (! isset($allowed[$table]) || isset($seen[$key]) || array_diff(array_keys($entry["update"]), $allowed[$table])) {
        throw new \RuntimeException("Unsupported table, field, or duplicate row.");
    }
    $seen[$key] = true;
    $precision = json_decode($entry["update"]["date_precision"] ?? $entry["expected"]["date_precision"] ?? "{}", true, 512, JSON_THROW_ON_ERROR);
    foreach ($entry["update"] as $field => $value) {
        if ($field === "date_precision") { continue; }
        if (! array_key_exists($field, $entry["expected"]) || ! in_array($entry["expected"][$field], [null, ""], true)) {
            throw new \RuntimeException("Only missing fields may be filled.");
        }
        if ($field === "photo") { continue; }
        $date = \DateTimeImmutable::createFromFormat("!Y-m-d H:i:s", $value);
        if (! $date || $date->format("Y-m-d H:i:s") !== $value || ! in_array($precision[$field] ?? null, ["day", "month", "year"], true)) {
            throw new \RuntimeException("Invalid date or precision.");
        }
        if (($precision[$field] === "month" && substr($value, 8, 2) !== "01") || ($precision[$field] === "year" && substr($value, 5, 5) !== "01-01")) {
            throw new \RuntimeException("Invalid partial date storage.");
        }
    }
    $oldPrecision = json_decode($entry["expected"]["date_precision"] ?? "{}", true, 512, JSON_THROW_ON_ERROR);
    foreach ($oldPrecision as $field => $value) {
        if (($precision[$field] ?? null) !== $value) { throw new \RuntimeException("Existing precision must be preserved."); }
    }
    if (isset($entry["asset"])) {
        $asset = $entry["asset"];
        if (! preg_match("/^[a-z0-9-]+[.]png$/", $asset["file"]) || ! preg_match("/^[a-f0-9]{64}$/", $asset["sha256"]) || ($entry["update"]["photo"] ?? null) !== "prisoners/".$asset["file"]) {
            throw new \RuntimeException("Invalid photo path or checksum.");
        }
        $bytes = File::get(base_path("database/data/photos/".$asset["file"]));
        $size = getimagesizefromstring($bytes);
        if (! $size || $size[2] !== IMAGETYPE_PNG || hash("sha256", $bytes) !== $asset["sha256"]) {
            throw new \RuntimeException("Invalid portrait bytes: ".$asset["file"]);
        }
        $path = $entry["update"]["photo"];
        if ($disk->exists($path) && hash("sha256", $disk->get($path)) !== $asset["sha256"]) {
            throw new \RuntimeException("Different existing photo file: ".$path);
        }
        $assets[$key] = ["path" => $path, "bytes" => $bytes, "sha256" => $asset["sha256"]];
    } elseif (isset($entry["update"]["photo"])) {
        throw new \RuntimeException("Photo update requires a verified asset.");
    }
}
$matches = function ($row, $expected) {
    foreach ($expected as $field => $value) {
        if (! array_key_exists($field, $row)) { return false; }
        if ($field === "date_precision") {
            if (json_decode($row[$field] ?? "{}", true) != json_decode($value ?? "{}", true)) { return false; }
        } elseif ($row[$field] !== $value) { return false; }
    }
    return true;
};
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$changed = DB::transaction(function () use ($payload, $matches, $dryRun, $disk, $assets) {
    $plans = [];
    foreach ($payload["entries"] as $entry) {
        $person = DB::table("prisoners")->where("id", $entry["prisoner_id"])->lockForUpdate()->first();
        if (! $person || $person->name !== $entry["name"] || $person->slug !== $entry["slug"]) {
            throw new \RuntimeException("Identity mismatch: ".$entry["name"]);
        }
        $record = DB::table($entry["table"])->where("id", $entry["id"])->lockForUpdate()->first();
        if (! $record || ($entry["table"] === "prisoner_cases" && $record->prisoner_id !== $entry["prisoner_id"])) {
            throw new \RuntimeException("Missing or mismatched row: ".$entry["id"]);
        }
        $row = (array) $record;
        $key = $entry["table"]."/".$entry["id"];
        if ($matches($row, array_replace($entry["expected"], $entry["update"]))) {
            if (isset($assets[$key]) && ! $disk->exists($assets[$key]["path"])) {
                throw new \RuntimeException("Applied portrait is missing from storage.");
            }
            echo "Already applied: ", $entry["name"], " / ", $entry["table"], "\n";
            continue;
        }
        if (! $matches($row, $entry["expected"])) {
            throw new \RuntimeException("Row changed since audit: ".$entry["name"]." / ".$entry["table"]);
        }
        $plans[] = ["entry" => $entry, "row" => $row, "key" => $key];
    }
    // All database identities, missing fields and image collisions pass before writes.
    foreach ($plans as $plan) {
        $entry = $plan["entry"];
        echo ($dryRun ? "Would fill missing fields: " : "Filling missing fields: "), $entry["name"], " / ", $entry["table"], "\n";
        if ($dryRun) { continue; }
        if (isset($assets[$plan["key"]])) {
            $asset = $assets[$plan["key"]];
            if (! $disk->exists($asset["path"]) && ! $disk->put($asset["path"], $asset["bytes"], "public")) {
                throw new \RuntimeException("Portrait copy failed.");
            }
            if (hash("sha256", $disk->get($asset["path"])) !== $asset["sha256"]) {
                throw new \RuntimeException("Portrait copy verification failed.");
            }
        }
        $query = DB::table($entry["table"])->where("id", $entry["id"]);
        foreach ($entry["expected"] as $field => $value) { $query->where($field, $plan["row"][$field]); }
        if ($query->update(array_replace($entry["update"], ["updated_at" => now()])) !== 1) {
            throw new \RuntimeException("Concurrent edit; database transaction rolled back.");
        }
    }
    return count($plans);
});
if (! $dryRun && $changed > 0) { Cache::forget(PrisonerApiController::cacheKey()); }
echo ($dryRun ? "Would update rows: " : "Updated rows: "), $changed, "\n";
echo "B433-OK\n";
'
run "fill-verified-1950s-missing-fields" "B433-OK" "$UPDATE_CODE" || exit 1
