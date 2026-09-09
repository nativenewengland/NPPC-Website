#!/usr/bin/env bash
# Batch 257: consolidate NPPC Communications into the existing coalition author.
# After merging, pull main and apply earlier pending batches in order.
#   sudo -u www-data bash database/data/run-batch-257.sh --dry-run
#   sudo -u www-data bash database/data/run-batch-257.sh
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-257.sh [--dry-run]" >&2
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
use App\Models\Author;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

$p = json_decode(File::get(base_path("database/data/fixes/batch257.json")), true, 512, JSON_THROW_ON_ERROR);
if ($p["batch"] !== 257 || count($p["articles"]) !== 3
    || count(array_unique($p["articles"])) !== 3
    || $p["source"]["slug"] !== "nppc-communications"
    || $p["target"]["slug"] !== "national-political-prisoner-coalition") {
    throw new \RuntimeException("Unexpected batch payload.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
DB::transaction(function () use ($p, $dryRun) {
    $target = Author::where("slug", $p["target"]["slug"])->lockForUpdate()->sole();
    if ($target->id !== $p["target"]["id"] || $target->name !== $p["target"]["name"]) {
        throw new \RuntimeException("Target author identity mismatch.");
    }
    $sources = Author::where("id", $p["source"]["id"])
        ->orWhere("slug", $p["source"]["slug"])->orWhere("name", $p["source"]["name"])
        ->lockForUpdate()->get();
    if ($sources->count() > 1) {
        throw new \RuntimeException("Multiple source identities found.");
    }
    $source = $sources->first();
    if ($source && ($source->id !== $p["source"]["id"] || $source->name !== $p["source"]["name"]
        || $source->slug !== $p["source"]["slug"])) {
        throw new \RuntimeException("Source author identity mismatch.");
    }
    $articles = Article::withoutGlobalScopes()->whereIn("slug", $p["articles"])
        ->lockForUpdate()->get(["id", "slug", "author_id"]);
    if ($articles->count() !== 3 || $articles->pluck("slug")->unique()->count() !== 3) {
        throw new \RuntimeException("Expected articles missing or duplicated.");
    }
    foreach ($articles as $article) {
        if ($article->author_id !== $target->id && (! $source || $article->author_id !== $source->id)) {
            throw new \RuntimeException("Unexpected author on article: ".$article->slug);
        }
    }
    if (Article::withoutGlobalScopes()->where("author_id", $p["source"]["id"])
        ->whereNotIn("slug", $p["articles"])->exists()) {
        throw new \RuntimeException("Additional source articles need review before merging.");
    }
    $moving = $articles->where("author_id", $p["source"]["id"]);
    foreach ($moving as $article) {
        echo ($dryRun ? "Would reassign: " : "Reassigning: "), $article->slug, "\n";
    }
    if ($dryRun) {
        echo "Would reassign articles: ", $moving->count(), "\n";
        echo ($source ? "Would remove duplicate author.\n" : "Already consolidated.\n");
        return;
    }
    if ($moving->isNotEmpty()) {
        $count = Article::withoutGlobalScopes()->whereIn("id", $moving->pluck("id"))
            ->where("author_id", $source->id)->toBase()->update(["author_id" => $target->id]);
        if ($count !== $moving->count()) {
            throw new \RuntimeException("Concurrent author change; rolling back.");
        }
    }
    if ($source) {
        if (Article::withoutGlobalScopes()->where("author_id", $source->id)->exists()) {
            throw new \RuntimeException("Duplicate author still has articles; rolling back.");
        }
        if (Author::whereKey($source->id)->where("name", $p["source"]["name"])
            ->where("slug", $p["source"]["slug"])->toBase()->delete() !== 1) {
            throw new \RuntimeException("Duplicate author changed; rolling back.");
        }
    }
    echo "Reassigned articles: ", $moving->count(), "\n";
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
    Cache::forget("museum:payload:v2");
}
echo "B257-OK\n";
'
run "consolidate-nppc-author" "B257-OK" "$UPDATE_CODE" || exit 1
