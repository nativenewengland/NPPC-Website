#!/usr/bin/env bash
# Batch 250: fill verified missing dates and one portrait; NEVER edit biographies.
# After git pull and earlier pending batches, including 249:
#   bash database/data/run-batch-250.sh --dry-run
#   bash database/data/run-batch-250.sh
# Provenance: fixes/batch250.json; audit: audits/missing-fields-2026-09-07.md.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-250.sh [--dry-run]" >&2
    exit 2
fi
run() {
    local label="$1" sentinel="$2" code="$3" out status=0
    echo "--- ${label}"
    out=$(php artisan tinker --execute="$code" 2>&1) || status=$?
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

$payload = json_decode(File::get(base_path("database/data/fixes/batch250.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 250 || ($payload["expected_count"] ?? null) !== 19 || count($payload["entries"] ?? []) !== 19) {
    throw new \RuntimeException("Unexpected batch identity or count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$disk = Storage::disk("public");
$validateDates = function ($dates, $allowed) {
    if (! is_array($dates) || array_diff(array_keys($dates), $allowed)) {
        throw new \RuntimeException("Unsupported date field.");
    }
    foreach ($dates as $parts) {
        Validator::make($parts, [
            "year" => "required|integer|between:1700,2026",
            "month" => "sometimes|required|integer|between:1,12",
            "day" => "sometimes|required|integer|between:1,31",
            "approximate" => "sometimes|required|boolean",
        ])->validate();
        if ((isset($parts["day"]) && ! isset($parts["month"])) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) {
            throw new \RuntimeException("Invalid partial date.");
        }
    }
};
$assets = [];
$seen = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, [
        "slug" => "required|string|regex:/^[a-z0-9-]+$/",
        "name" => "required|string",
        "description" => "prohibited",
        "append_description" => "prohibited",
        "website" => "prohibited",
        "cases" => "sometimes|required|array",
        "cases.*.match_charges" => "required|string",
        "cases.*.match_incarceration_date" => "required|date_format:Y-m-d",
        "cases.*.dates" => "required|array|min:1",
        "photo.file" => "required_with:photo|string|regex:/^[a-z0-9-]+[.]jpg$/",
        "photo.sha256" => "required_with:photo|string|size:64|regex:/^[a-f0-9]+$/",
    ])->validate();
    if (isset($seen[$entry["slug"]])) {
        throw new \RuntimeException("Duplicate profile in payload.");
    }
    $seen[$entry["slug"]] = true;
    $validateDates($entry["dates"] ?? [], ["birthdate", "death_date"]);
    foreach ($entry["cases"] ?? [] as $case) {
        // This batch has no new custody endpoints. Avoid duration side effects.
        $validateDates($case["dates"], ["arrest_date", "sentenced_date"]);
    }
    if (isset($entry["photo"])) {
        $filename = $entry["photo"]["file"];
        $bytes = File::get(base_path("database/data/photos/".$filename));
        $size = getimagesizefromstring($bytes);
        if (! $size || $size[2] !== IMAGETYPE_JPEG || hash("sha256", $bytes) !== $entry["photo"]["sha256"]) {
            throw new \RuntimeException("Invalid portrait or checksum: ".$filename);
        }
        $assets[$entry["slug"]] = ["path" => "prisoners/".$filename, "bytes" => $bytes];
    }
}
$fillDates = function ($record, $dates) {
    foreach ($dates as $field => $parts) {
        if ($record->getRawOriginal($field) === null || $record->getRawOriginal($field) === "") {
            $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null, $parts["approximate"] ?? false);
        } else {
            echo "Preserved existing ", $field, " for ", $record->id, "\n";
        }
    }
};
$changed = DB::transaction(function () use ($payload, $dryRun, $disk, $assets, $fillDates) {
    $plans = [];
    $copies = [];
    $queue = function ($record, $allowed) use (&$plans) {
        if (! $record->isDirty()) { return; }
        $key = $record->getTable().":".$record->id;
        if (isset($plans[$key]) || array_diff(array_keys($record->getDirty()), $allowed)) {
            throw new \RuntimeException("Duplicate target or unsupported field update.");
        }
        $plans[$key] = $record;
    };
    foreach ($payload["entries"] as $entry) {
        $record = Prisoner::withoutGlobalScopes()->where("slug", $entry["slug"])->lockForUpdate()->sole();
        if ($record->name !== $entry["name"]) {
            throw new \RuntimeException("Identity mismatch: ".$entry["slug"]);
        }
        $fillDates($record, $entry["dates"] ?? []);
        if (isset($assets[$entry["slug"]]) && ! $record->photo) {
            $asset = $assets[$entry["slug"]];
            if ($disk->exists($asset["path"])) {
                if (hash("sha256", $disk->get($asset["path"])) !== $entry["photo"]["sha256"]) {
                    throw new \RuntimeException("Different existing photo file: ".$asset["path"]);
                }
            } else {
                $copies[$asset["path"]] = $asset["bytes"];
            }
            $record->photo = $asset["path"];
        }
        $queue($record, ["birthdate", "death_date", "date_precision", "photo"]);
        foreach ($entry["cases"] ?? [] as $caseData) {
            $case = $record->cases()->where("charges", $caseData["match_charges"])->whereDate("incarceration_date", $caseData["match_incarceration_date"])->lockForUpdate()->sole();
            $fillDates($case, $caseData["dates"]);
            $queue($case, ["arrest_date", "sentenced_date", "date_precision"]);
        }
    }
    // Every identity and asset is checked before the first write.
    foreach ($copies as $path => $bytes) {
        echo ($dryRun ? "Would copy: " : "Copying: "), $path, "\n";
        if (! $dryRun && (! $disk->put($path, $bytes, "public") || hash("sha256", $disk->get($path)) !== hash("sha256", $bytes))) {
            throw new \RuntimeException("Photo copy failed: ".$path);
        }
    }
    foreach ($plans as $record) {
        $changes = $record->getDirty();
        echo ($dryRun ? "Would fill: " : "Filling: "), $record->getTable(), " / ", $record->id, " [", implode(", ", array_keys($changes)), "]\n";
        if (! $dryRun) {
            // Deliberate guarded Eloquent update: saving hooks can overwrite
            // release dates or derive exile fields outside this fill-only task.
            // Compare original values too, so a concurrent edit is never lost.
            $query = $record->newQueryWithoutScopes()->whereKey($record->id);
            foreach (array_keys($changes) as $field) {
                $query->where($field, $record->getRawOriginal($field));
            }
            if ($query->update($changes) !== 1) {
                throw new \RuntimeException("Concurrent edit detected; transaction rolled back.");
            }
        }
    }
    return count($plans);
});
if (! $dryRun) {
    Cache::forget(PrisonerApiController::cacheKey());
}
echo ($dryRun ? "Would update rows: " : "Updated rows: "), $changed, "\n";
echo "B250-OK\n";
'
run "fill-verified-missing-fields" "B250-OK" "$UPDATE_CODE" || exit 1
echo "Batch 250 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
