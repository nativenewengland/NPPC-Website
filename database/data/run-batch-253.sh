#!/usr/bin/env bash
# Batch 253: five American WWII conscientious objectors.
# After merging, pulling main and applying earlier pending batches, including 252:
#   bash database/data/run-batch-253.sh --dry-run
#   bash database/data/run-batch-253.sh
# Missing people only. Existing profiles and cases are preserved.
# Source notes and qualifications: fixes/batch253.json.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-253.sh [--dry-run]" >&2
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

ADD_CODE='
use App\Models\Prisoner;
use App\Models\Institution;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch253.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 253 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($name) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $name))));
$seen = [];
$assets = [];
$disk = Storage::disk("public");
$validateDates = function ($dates, $allowed) {
    foreach ($dates as $field => $parts) {
        if (! in_array($field, $allowed, true) || ! is_array($parts)) { throw new \RuntimeException("Unsupported date field."); }
        Validator::make($parts, ["year" => "required|integer|between:1800,2026", "month" => "sometimes|integer|between:1,12", "day" => "sometimes|required_with:month|integer|between:1,31"])->validate();
        if (isset($parts["day"]) && ! isset($parts["month"]) || ! checkdate($parts["month"] ?? 1, $parts["day"] ?? 1, $parts["year"])) { throw new \RuntimeException("Invalid date."); }
    }
};
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, [
        "match_names" => "required|array|min:1",
        "match_names.*" => "required|string",
        "prisoner.name" => "required|string|max:255",
        "prisoner.first_name" => "required|string|max:255",
        "prisoner.last_name" => "required|string|max:255",
        "prisoner.description" => "required|string",
        "prisoner.state" => "required|string",
        "prisoner.era" => "required|in:1940s",
        "prisoner.in_custody" => "required|boolean|declined",
        "prisoner.released" => "required|boolean|accepted",
        "prisoner.website" => "prohibited",
        "prisoner.birthdate" => "prohibited",
        "prisoner.death_date" => "prohibited",
        "prisoner.photo" => "prohibited",
        "prisoner.cases" => "required|array|size:1",
        "prisoner.cases.*.charges" => "required|string|max:255",
        "prisoner.cases.*.convicted" => "sometimes|string|max:255",
        "prisoner.cases.*.sentence" => "required|string",
        "prisoner.cases.*.arrest_date" => "prohibited",
        "prisoner.cases.*.incarceration_date" => "prohibited",
        "prisoner.cases.*.release_date" => "prohibited",
        "dates" => "required|array",
        "case_dates" => "sometimes|array",
        "documented_months" => "sometimes|integer|in:24",
        "photo.file" => ["required_with:photo", "string", "regex:/^[a-z0-9-]+[.](jpg|png)$/"],
        "photo.sha256" => "required_with:photo|string|regex:/^[a-f0-9]{64}$/",
    ])->validate();
    $validateDates($entry["dates"], ["birthdate", "death_date"]);
    $validateDates($entry["case_dates"] ?? [], ["arrest_date", "incarceration_date", "release_date", "sentenced_date"]);
    if (isset($entry["photo"])) {
        $photo = $entry["photo"];
        $bytes = File::get(base_path("database/data/photos/".$photo["file"]));
        $size = getimagesizefromstring($bytes);
        if (! $size || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || hash("sha256", $bytes) !== $photo["sha256"]) { throw new \RuntimeException("Invalid photo: ".$photo["file"]); }
        $path = "prisoners/".$photo["file"];
        if ($disk->exists($path) && hash("sha256", $disk->get($path)) !== $photo["sha256"]) { throw new \RuntimeException("Conflicting existing photo: ".$path); }
        $assets[$entry["prisoner"]["name"]] = ["path" => $path, "bytes" => $bytes];
    }
    $caseData = $entry["prisoner"]["cases"][0];
    if (isset($caseData["institution_name"])) {
        // Reuse a verified existing institution; do not silently create a map location.
        $institution = Institution::where("name", $caseData["institution_name"])->sole();
        if ($institution->city !== $caseData["institution_city"] || $institution->state !== $caseData["institution_state"] || $institution->lat === null || $institution->lng === null) { throw new \RuntimeException("Institution identity or coordinates mismatch."); }
    }
    $names = array_map($normalize, $entry["match_names"]);
    if (! in_array($normalize($entry["prisoner"]["name"]), $names, true)) {
        throw new \RuntimeException("Canonical name missing from aliases.");
    }
    foreach (array_unique($names) as $name) {
        if ($name === "" || isset($seen[$name])) {
            throw new \RuntimeException("Empty or overlapping match name: ".$name);
        }
        $seen[$name] = true;
    }
}

$counts = DB::transaction(function () use ($payload, $normalize, $dryRun, $assets, $disk) {
    // Include under-review records. Normalize punctuation and accents and
    // match whole name phrases in aliases, never a surname substring.
    $records = Prisoner::withoutGlobalScopes()->get(["id", "name", "first_name", "middle_name", "last_name", "aka", "slug"]);
    $missing = [];
    $present = 0;
    foreach ($payload["entries"] as $entry) {
        $names = array_unique(array_map($normalize, $entry["match_names"]));
        $matches = $records->filter(function ($record) use ($names, $normalize) {
            $values = array_map($normalize, [$record->name, $record->slug, $record->aka, $record->first_name." ".$record->last_name, $record->first_name." ".$record->middle_name." ".$record->last_name]);
            foreach ($values as $value) {
                foreach ($names as $name) {
                    if (str_contains(" ".$value." ", " ".$name." ")) {
                        return true;
                    }
                }
            }
            return false;
        });
        if ($matches->count() > 1) {
            throw new \RuntimeException("Ambiguous existing profiles for ".$entry["prisoner"]["name"].": ".$matches->pluck("name")->implode(", "));
        }
        if ($matches->isNotEmpty()) {
            echo "Preserved existing: ", $matches->first()->name, "\n";
            $present++;
        } else {
            $missing[] = $entry;
        }
    }
    // All identities are checked before the first write. The outer transaction
    // covers the command, cases, and its normal sort-order shifts together.
    foreach ($missing as $entry) {
        $data = $entry["prisoner"];
        if ($dryRun) {
            echo "Would add: ", $data["name"], "\n";
            continue;
        }
        $status = Artisan::call("prisoner:add", ["json" => json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)]);
        if ($status !== 0) {
            throw new \RuntimeException("prisoner:add failed: ".$data["name"]."\n".Artisan::output());
        }
        $record = Prisoner::withoutGlobalScopes()->where("name", $data["name"])->sole();
        if ($record->cases()->count() !== 1) {
            throw new \RuntimeException("Unexpected case count: ".$data["name"]);
        }
        $case = $record->cases()->sole();
        foreach ($entry["dates"] as $field => $parts) {
            $record->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null);
        }
        if (isset($assets[$data["name"]])) {
            $asset = $assets[$data["name"]];
            if (! $disk->exists($asset["path"]) && ! $disk->put($asset["path"], $asset["bytes"], "public")) { throw new \RuntimeException("Photo copy failed."); }
            if (hash("sha256", $disk->get($asset["path"])) !== $entry["photo"]["sha256"]) { throw new \RuntimeException("Photo verification failed."); }
            $record->photo = $asset["path"];
        }
        $record->save();
        foreach ($entry["case_dates"] ?? [] as $field => $parts) {
            $case->setPartialDate($field, $parts["year"], $parts["month"] ?? null, $parts["day"] ?? null);
        }
        if (isset($entry["documented_months"])) { $case->imprisoned_for_months = $entry["documented_months"]; }
        $case->save();
        if ($record->in_custody || ! $record->released || $record->website) { throw new \RuntimeException("Unexpected custody state or support URL."); }
        echo "Added: ", $record->name, " /prisoner/", $record->slug, "\n";
    }
    return ["present" => $present, "missing" => count($missing)];
});

if (! $dryRun && $counts["missing"] > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
}
echo "Existing profiles preserved: ", $counts["present"], "\n";
echo ($dryRun ? "Would add: " : "Added: "), $counts["missing"], "\n";
echo "B253-OK\n";
'

run "add-wwii-conscientious-objectors" "B253-OK" "$ADD_CODE" || exit 1
echo "Batch 253 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
