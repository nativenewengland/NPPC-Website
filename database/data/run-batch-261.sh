#!/usr/bin/env bash
# Batch 261: fill 100 missing prisoner photos; preserve every attached photo.
# After merging, pull main and apply earlier pending batches in order.
#   sudo -u www-data bash database/data/run-batch-261.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-261.sh
# Provenance and rights: fixes/batch261.json; photos/CREDITS-batch261.md.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-261.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch261.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 261 || ($payload["expected_count"] ?? null) !== 100 || count($payload["entries"] ?? []) !== 100) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$disk = Storage::disk("public");
$assets = [];
$seen = [];
foreach ($payload["entries"] as $entry) {
    if (array_diff(array_keys($entry), ["id", "slug", "name", "photo", "source_url", "image_url", "credit", "rights", "identity_evidence", "image_edit"])) {
        throw new \RuntimeException("Unsupported payload field; photo-only batch.");
    }
    Validator::make($entry, [
        "id" => "required|uuid",
        "slug" => "required|string|regex:/^[a-z0-9-]+$/",
        "name" => "required|string",
        "photo" => "required|array:file,sha256",
        "photo.file" => ["required", "string", "regex:/^[a-z0-9-]+[.](jpg|png)$/"],
        "photo.sha256" => "required|string|size:64|regex:/^[a-f0-9]+$/",
        "source_url" => "required|url",
    ])->validate();
    $filename = $entry["photo"]["file"];
    if (isset($seen[$entry["id"]]) || isset($seen[$entry["slug"]]) || isset($seen[$filename])) {
        throw new \RuntimeException("Duplicate profile or asset in payload.");
    }
    $seen[$entry["id"]] = $seen[$entry["slug"]] = $seen[$filename] = true;
    $bytes = File::get(base_path("database/data/photos/".$filename));
    $size = getimagesizefromstring($bytes);
    $type = str_ends_with($filename, ".png") ? IMAGETYPE_PNG : IMAGETYPE_JPEG;
    if (! $size || $size[2] !== $type || hash("sha256", $bytes) !== $entry["photo"]["sha256"]) {
        throw new \RuntimeException("Invalid portrait or checksum: ".$filename);
    }
    $assets[$entry["id"]] = ["path" => "prisoners/".$filename, "bytes" => $bytes];
}
$changed = DB::transaction(function () use ($payload, $dryRun, $disk, $assets) {
    $plans = [];
    foreach ($payload["entries"] as $entry) {
        $record = Prisoner::withoutGlobalScopes()->where("slug", $entry["slug"])->lockForUpdate()->sole();
        if ($record->id !== $entry["id"] || $record->name !== $entry["name"]) {
            throw new \RuntimeException("Identity mismatch: ".$entry["slug"]);
        }
        $original = $record->getRawOriginal("photo");
        if ($original !== null && $original !== "") {
            echo "Preserved existing photo: ", $record->name, "\n";
            continue;
        }
        $asset = $assets[$entry["id"]];
        if ($disk->exists($asset["path"]) && hash("sha256", $disk->get($asset["path"])) !== $entry["photo"]["sha256"]) {
            throw new \RuntimeException("Different existing photo file: ".$asset["path"]);
        }
        $plans[] = ["record" => $record, "original" => $original, "asset" => $asset];
    }
    // All identities and destination collisions checked before any write.
    foreach ($plans as $plan) {
        $record = $plan["record"];
        $asset = $plan["asset"];
        echo ($dryRun ? "Would fill photo: " : "Filling photo: "), $record->name, "\n";
        if ($dryRun) { continue; }
        if (! $disk->exists($asset["path"])) {
            if (! $disk->put($asset["path"], $asset["bytes"], "public")) {
                throw new \RuntimeException("Photo copy failed: ".$asset["path"]);
            }
        }
        if (hash("sha256", $disk->get($asset["path"])) !== hash("sha256", $asset["bytes"])) {
            throw new \RuntimeException("Photo verification failed: ".$asset["path"]);
        }
        // Guarded Eloquent update avoids biography/custody saving hooks.
        // A concurrent photo change rolls back the transaction, never overwrites it.
        $count = Prisoner::withoutGlobalScopes()->whereKey($record->id)
            ->where("photo", $plan["original"])->update(["photo" => $asset["path"]]);
        if ($count !== 1) {
            throw new \RuntimeException("Concurrent photo edit detected; transaction rolled back.");
        }
    }
    return count($plans);
});
if (! $dryRun && $changed > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
}
echo ($dryRun ? "Would update rows: " : "Updated rows: "), $changed, "\n";
echo "B261-OK\n";
'
run "fill-missing-portraits" "B261-OK" "$UPDATE_CODE" || exit 1
