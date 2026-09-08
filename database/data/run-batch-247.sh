#!/usr/bin/env bash
# Batch 247: add three user-requested news links with dated map markers.
# Run after git pull and earlier pending numbered batches, including 246:
#   bash database/data/run-batch-247.sh
# Read-only preview:
#   bash database/data/run-batch-247.sh --dry-run
#
# Match canonical URL plus displayed date. Existing rows are verified and
# preserved; missing rows are inserted together. Research notes live in JSON.
# No migrations or prisoner/institution writes.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-247.sh [--dry-run]" >&2
    exit 2
fi

run() {
    local label="$1" sentinel="$2" code="$3" out status=0
    echo "--- ${label}"
    out=$(php artisan tinker --execute="$code" 2>&1) || status=$?
    printf '%s\n' "$out"
    # Tinker can report an exception while exiting zero. Require both signals.
    if [[ $status -ne 0 ]] || ! grep -Fxq "$sentinel" <<<"$out"; then
        echo "FAILED: ${label}" >&2
        return 1
    fi
}

ADD_CODE='
use App\Models\DashboardLink;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch247.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 247 || ($payload["expected_count"] ?? null) !== 3 || count($payload["entries"] ?? []) !== 3) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$fields = array_flip(["title", "url", "source", "category", "published_at", "lat", "lng", "location_label"]);
$links = [];
$seen = [];
foreach ($payload["entries"] as $entry) {
    $link = array_intersect_key($entry, $fields);
    Validator::make($link, [
        "title" => "required|string|max:255",
        "url" => "required|url|max:2048",
        "source" => "required|string|max:255",
        "category" => "required|in:".implode(",", array_keys(DashboardLink::CATEGORIES)),
        "published_at" => "required|date_format:Y-m-d|after_or_equal:2025-05-07|before_or_equal:today",
        "lat" => "required|numeric|between:-90,90",
        "lng" => "required|numeric|between:-180,180",
        "location_label" => "required|string|max:255",
    ])->validate();
    $key = $link["url"]."|".$link["published_at"];
    if (isset($seen[$key])) {
        throw new \RuntimeException("Duplicate dated source in payload: ".$key);
    }
    $seen[$key] = true;
    $links[] = $link;
}

$counts = DB::transaction(function () use ($links, $dryRun) {
    $missing = [];
    $present = 0;
    // Complete preflight before any inserts. A conflicting existing row is
    // reported for review, never silently replaced by the historical payload.
    foreach ($links as $link) {
        $matches = DashboardLink::where("url", $link["url"])
            ->whereDate("published_at", $link["published_at"])->get();
        if ($matches->count() > 1) {
            throw new \RuntimeException("Multiple rows for dated source: ".$link["url"]);
        }
        if ($matches->isEmpty()) {
            $missing[] = $link;
            continue;
        }
        $record = $matches->first();
        foreach ($link as $field => $expected) {
            $actual = $record->{$field};
            if ($field === "published_at") {
                $same = $actual?->toDateString() === $expected;
            } elseif ($field === "lat" || $field === "lng") {
                $same = $actual !== null && round((float) $actual, 7) === round((float) $expected, 7);
            } else {
                $same = (string) $actual === (string) $expected;
            }
            if (! $same) {
                throw new \RuntimeException("Existing record differs in ".$field.": ".$link["url"]);
            }
        }
        $present++;
    }
    foreach ($missing as $link) {
        if (! $dryRun) {
            DashboardLink::create($link);
        }
        echo ($dryRun ? "Would insert: " : "Inserted: "), $link["published_at"], " ", $link["title"], "\n";
    }
    return ["present" => $present, "missing" => count($missing)];
});

if (! $dryRun) {
    // Standard batch invalidation. Event markers themselves query DashboardLink
    // directly; this also clears the separate cached prisoner API.
    Cache::forget(PrisonerApiController::cacheKey());
}
echo "Already present and verified: ", $counts["present"], "\n";
echo ($dryRun ? "Would insert: " : "Inserted: "), $counts["missing"], "\n";
echo "Mapped total: ", DashboardLink::onMap()->count(), "\n";
echo "B247-OK\n";
'

run "reconcile-dashboard-events" "B247-OK" "$ADD_CODE" || exit 1
echo "Batch 247 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
