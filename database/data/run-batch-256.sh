#!/usr/bin/env bash
# Batch 256: replace the store announcement image with the approved tote mockup.
# After merging, pull main and apply earlier pending batches in order.
#   sudo -u www-data bash database/data/run-batch-256.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-256.sh
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-256.sh [--dry-run]" >&2
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
use App\Models\Article;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

$p = json_decode(File::get(base_path("database/data/fixes/batch256.json")), true, 512, JSON_THROW_ON_ERROR);
if ($p["batch"] !== 256 || $p["slug"] !== "nppc-launches-online-store"
    || $p["file"] !== "database/data/files/articles/nppc-store-tote-wide.png"
    || $p["image"] !== "articles/nppc-store-tote-wide.png"
    || $p["expected_image"] !== "articles/nppc-store.png") {
    throw new \RuntimeException("Unexpected batch target or asset path.");
}
$bytes = File::get(base_path($p["file"]));
$size = getimagesizefromstring($bytes);
if (! $size || $size[2] !== IMAGETYPE_PNG || $size[0] !== $p["width"]
    || $size[1] !== $p["height"] || hash("sha256", $bytes) !== $p["sha256"]) {
    throw new \RuntimeException("Invalid image dimensions, format or checksum.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$disk = Storage::disk("public");
DB::transaction(function () use ($p, $bytes, $dryRun, $disk) {
    $article = Article::withoutGlobalScopes()->where("slug", $p["slug"])->lockForUpdate()->sole();
    if ($article->title !== $p["title"]) {
        throw new \RuntimeException("Article identity mismatch.");
    }
    $original = $article->getRawOriginal("image");
    if (! in_array($original, [$p["expected_image"], $p["image"]], true)) {
        throw new \RuntimeException("Image changed since preparation; refusing to overwrite it.");
    }
    if ($disk->exists($p["image"]) && hash("sha256", $disk->get($p["image"])) !== $p["sha256"]) {
        throw new \RuntimeException("Destination contains a different image.");
    }
    if ($dryRun) {
        echo "Verified article and image: ", $article->title, "\n";
        echo ($original === $p["image"] ? "Already attached: " : "Would attach: "), $p["image"], "\n";
        return;
    }
    if (! $disk->exists($p["image"]) && ! $disk->put($p["image"], $bytes, "public")) {
        throw new \RuntimeException("Image installation failed.");
    }
    if (hash("sha256", $disk->get($p["image"])) !== $p["sha256"]) {
        throw new \RuntimeException("Installed image failed verification.");
    }
    if ($original !== $p["image"]) {
        // Update only the image column; preserve text, dates, caption and metadata.
        $count = Article::withoutGlobalScopes()->whereKey($article->id)->where("image", $original)
            ->toBase()->update(["image" => $p["image"]]);
        if ($count !== 1) {
            throw new \RuntimeException("Concurrent article edit detected.");
        }
        echo "Attached: ", $p["image"], "\n";
    } else {
        echo "Already attached: ", $p["image"], "\n";
    }
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
}
echo "B256-OK\n";
'
run "replace-store-announcement-image" "B256-OK" "$UPDATE_CODE" || exit 1
