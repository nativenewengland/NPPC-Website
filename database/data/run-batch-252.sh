#!/usr/bin/env bash
# Batch 252: five jailed participants in the 1966 St. Petersburg mural protest.
# After merging, pulling main and applying earlier pending batches, including 251:
#   bash database/data/run-batch-252.sh --dry-run
#   bash database/data/run-batch-252.sh
# Missing people only. Existing profiles and cases are preserved.
# Source notes and qualifications: fixes/batch252.json.

set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-252.sh [--dry-run]" >&2
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
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

$payload = json_decode(File::get(base_path("database/data/fixes/batch252.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 252 || ($payload["expected_count"] ?? null) !== 5 || count($payload["entries"] ?? []) !== 5) {
    throw new \RuntimeException("Unexpected batch identity or entry count.");
}
$dryRun = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($name) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $name))));
$seen = [];
foreach ($payload["entries"] as $entry) {
    Validator::make($entry, [
        "match_names" => "required|array|min:1",
        "match_names.*" => "required|string",
        "prisoner.name" => "required|string|max:255",
        "prisoner.first_name" => "required|string|max:255",
        "prisoner.last_name" => "required|string|max:255",
        "prisoner.description" => "required|string",
        "prisoner.state" => "required|in:Florida",
        "prisoner.era" => "required|in:1960s",
        "prisoner.in_custody" => "required|boolean|declined",
        "prisoner.released" => "required|boolean|accepted",
        "prisoner.website" => "prohibited",
        "prisoner.cases" => "required|array|size:1",
        "prisoner.cases.*.charges" => "required|string",
        "prisoner.cases.*.convicted" => "required|string",
        "prisoner.cases.*.sentence" => "required|string",
        "prisoner.cases.*.arrest_date" => "required|date_format:Y-m-d|in:1966-12-29",
        "prisoner.cases.*.incarceration_date" => "required|date_format:Y-m-d|in:1966-12-29",
        "prisoner.cases.*.release_date" => "prohibited",
        "prisoner.cases.*.imprisoned_for_days" => "prohibited",
        "prisoner.cases.*.imprisoned_for_months" => "prohibited",
        "prisoner.birthdate" => "prohibited",
        "prisoner.death_date" => "prohibited",
        "prisoner.photo" => "prohibited",
    ])->validate();
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

$counts = DB::transaction(function () use ($payload, $normalize, $dryRun) {
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
            $missing[] = $entry["prisoner"];
        }
    }
    // All identities are checked before the first write. The outer transaction
    // covers the command, cases, and its normal sort-order shifts together.
    foreach ($missing as $data) {
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
        if ($case->imprisoned_for_days !== null || $case->imprisoned_for_months !== null || $case->release_date !== null || $record->in_custody || ! $record->released) {
            throw new \RuntimeException("Unexpected custody state or inferred duration; rolling back.");
        }
        echo "Added: ", $record->name, " /prisoner/", $record->slug, "\n";
    }
    return ["present" => $present, "missing" => count($missing)];
});

if (! $dryRun && $counts["missing"] > 0) {
    Cache::forget(PrisonerApiController::cacheKey());
}
echo "Existing profiles preserved: ", $counts["present"], "\n";
echo ($dryRun ? "Would add: " : "Added: "), $counts["missing"], "\n";
echo "B252-OK\n";
'

run "add-jailed-mural-protesters" "B252-OK" "$ADD_CODE" || exit 1
echo "Batch 252 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
