#!/usr/bin/env bash
# Batch 249: monuments defendants -- portraits, verified dates, website fixes.
# After git pull and earlier batches (including 248):
#   bash database/data/run-batch-249.sh --dry-run
#   bash database/data/run-batch-249.sh
# Source notes: fixes/batch249.json and photos/CREDITS-monuments-1965.md.
# Updates existing profiles only. Different existing dates/photos/websites stay.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-249.sh [--dry-run]" >&2
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch249.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 249 || ($payload["expected_count"] ?? null) !== 4 || count($payload["entries"] ?? []) !== 4) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$disk = Storage::disk("public");
$seen = [];
$assets = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, [
        "slug" => "required|string|regex:/^[a-z0-9-]+$/",
        "name" => "required|string",
        "clear_website_if_equals" => "sometimes|required|url",
        "append_description" => "prohibited",
        "dates" => "sometimes|required|array:birthdate,death_date",
        "dates.*.year" => "required|integer|between:1800,2026",
        "dates.*.month" => "sometimes|required|integer|between:1,12",
        "dates.*.day" => "sometimes|required|integer|between:1,31",
        "photo.sha256" => "required_with:photo|string|size:64|regex:/^[a-f0-9]+$/",
    ])->validate();
    if (isset($seen[$entry["slug"]])) {
        throw new \RuntimeException("Duplicate slug in payload.");
    }
    $seen[$entry["slug"]] = true;
    foreach ($entry["dates"] ?? [] as $parts) {
        if ((isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) {
            throw new \RuntimeException("Invalid partial date.");
        }
    }
    if (isset($entry["photo"])) {
        $filename = $entry["slug"]."-1965.jpg";
        $bytes = File::get(base_path("database/data/photos/nonfree/".$filename));
        $size = getimagesizefromstring($bytes);
        if (! $size || $size[2] !== IMAGETYPE_JPEG || hash("sha256", $bytes) !== $entry["photo"]["sha256"]) {
            throw new \RuntimeException("Invalid photo or checksum: ".$filename);
        }
        $assets[$entry["slug"]] = ["path" => "prisoners/".$filename, "bytes" => $bytes];
    }
}

$changed = DB::transaction(function () use ($payload, $dryRun, $disk, $assets) {
    $plans = [];
    $copies = [];
    // Preflight every identity and destination before saving or copying anything.
    foreach ($payload["entries"] as $entry) {
        $matches = Prisoner::withoutGlobalScopes()->where("slug", $entry["slug"])->lockForUpdate()->get();
        if ($matches->count() !== 1 || $matches->first()->name !== $entry["name"]) {
            throw new \RuntimeException("Missing or mismatched profile: ".$entry["slug"].". Check earlier batches including 248.");
        }
        $record = $matches->first();
        if (isset($entry["clear_website_if_equals"]) && $record->website) {
            if ($record->website === $entry["clear_website_if_equals"]) {
                $record->website = null;
            } else {
                echo "Preserved different website: ", $record->name, "\n";
            }
        }
        foreach ($entry["dates"] ?? [] as $field => $parts) {
            if (! $record->{$field}) {
                $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null);
            } else {
                echo "Preserved existing ", $field, ": ", $record->name, " = ", $record->partialDateIso($field), "\n";
            }
        }
        if (isset($assets[$entry["slug"]])) {
            $asset = $assets[$entry["slug"]];
            if (! $record->photo || $record->photo === $asset["path"]) {
                if ($disk->exists($asset["path"])) {
                    if (hash("sha256", $disk->get($asset["path"])) !== $entry["photo"]["sha256"]) {
                        throw new \RuntimeException("Photo destination has different content: ".$asset["path"]);
                    }
                } else {
                    $copies[$asset["path"]] = $asset["bytes"];
                }
                $record->photo = $asset["path"];
            } else {
                echo "Preserved existing photo: ", $record->name, "\n";
            }
        }
        if ($record->isDirty()) {
            echo ($dryRun ? "Would update: " : "Updating: "), $record->name, " [", implode(", ", array_keys($record->getDirty())), "]\n";
            $plans[] = $record;
        }
    }
    foreach ($copies as $path => $bytes) {
        echo ($dryRun ? "Would copy: " : "Copying: "), $path, "\n";
        if (! $dryRun && (! $disk->put($path, $bytes, "public") || hash("sha256", $disk->get($path)) !== hash("sha256", $bytes))) {
            throw new \RuntimeException("Photo copy failed: ".$path);
        }
    }
    if (! $dryRun) {
        foreach ($plans as $record) {
            $record->saveOrFail();
        }
    }
    // Copied files are immutable, checked on replay, and may safely remain
    // unreferenced if the database transaction fails after a successful copy.
    return count($plans);
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
}
echo ($dryRun ? "Would update profiles: " : "Updated profiles: "), $changed, "\n";
echo "B249-OK\n";
'

run "update-monuments-profiles" "B249-OK" "$UPDATE_CODE" || exit 1
echo "Batch 249 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
