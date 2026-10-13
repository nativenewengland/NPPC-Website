#!/usr/bin/env bash
# Batch 491: twenty researched missing dashboard news links.
# Canonical source identity guards URL variants; national reports stay unpinned.
# No updates to existing links, prisoner records, or cases.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-491.sh [--dry-run]" >&2
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

ADD_CODE='
use App\Models\DashboardLink;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

$payload = json_decode(File::get(base_path("database/data/fixes/batch491.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 491 || ($payload["expected_count"] ?? null) !== 20 || count($payload["entries"] ?? []) !== 20) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$fields = array_flip(["title", "url", "source", "category", "published_at", "lat", "lng", "location_label"]);
$links = [];
$seen = [];
$canonical = function ($url) {
    $host = strtolower(preg_replace("/^www[.]/", "", parse_url($url, PHP_URL_HOST) ?? ""));
    $path = rtrim(parse_url($url, PHP_URL_PATH) ?? "", "/");
    if ($host === "apnews.com" && preg_match("/[a-f0-9]{32}/", $path, $match)) { return "ap:".$match[0]; }
    return $host.$path;
};
foreach ($payload["entries"] as $entry) {
    $link = array_intersect_key($entry, $fields);
    Validator::make($link, [
        "title" => "required|string|max:255",
        "url" => "required|url|max:2048",
        "source" => "required|string|max:255",
        "category" => "required|in:".implode(",", array_keys(DashboardLink::CATEGORIES)),
        "published_at" => "required|date_format:Y-m-d H:i:s|after_or_equal:2025-05-07|before_or_equal:now",
        "lat" => "nullable|numeric|between:-90,90",
        "lng" => "nullable|numeric|between:-180,180",
        "location_label" => "required|string|max:255",
    ])->validate();
    $key = $canonical($link["url"]);
    if (isset($seen[$key])) {
        throw new \RuntimeException("Duplicate dated source in payload: ".$key);
    }
    $seen[$key] = true;
    if (($link["lat"] === null) !== ($link["lng"] === null)) {
        throw new \RuntimeException("Coordinates must be a pair or both null.");
    }
    $links[] = $link;
}

$counts = DB::transaction(function () use ($links, $dryRun, $canonical) {
    $missing = [];
    $present = 0;
    // Complete preflight before any inserts. A conflicting existing row is
    // reported for review, never silently replaced by the historical payload.
    $existing = DashboardLink::query()->lockForUpdate()->get()->groupBy(fn ($row) => $canonical($row->url));
    foreach ($links as $link) {
        $matches = $existing->get($canonical($link["url"]), collect());
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
                $same = $actual?->format("Y-m-d H:i:s") === $expected;
            } elseif ($field === "lat" || $field === "lng") {
                $same = $expected === null ? $actual === null : ($actual !== null && round((float) $actual, 7) === round((float) $expected, 7));
            } elseif ($field === "url") {
                $same = $canonical($actual) === $canonical($expected);
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

if (! $dryRun && $counts["missing"] > 0) {
    // Standard batch invalidation. Event markers themselves query DashboardLink
    // directly; this also clears the separate cached prisoner API.
    Cache::forget(PrisonerApiController::cacheKey());
}
echo "Already present and verified: ", $counts["present"], "\n";
echo ($dryRun ? "Would insert: " : "Inserted: "), $counts["missing"], "\n";
echo "Mapped total: ", DashboardLink::onMap()->count(), "\n";
echo "B491-OK\n";
'

run "reconcile-dashboard-events" "B491-OK" "$ADD_CODE" || exit 1
echo "Batch 491 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
