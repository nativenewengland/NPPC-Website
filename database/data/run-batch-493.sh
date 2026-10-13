#!/usr/bin/env bash
# Batch 493: add missing prisoner Jason Watson and his ongoing military case.
# Includes under-review records in identity preflight; replay preserves existing records.
# Uses normal curated sort placement; no existing biographies or cases are changed.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-493.sh [--dry-run]" >&2
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
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\PrisonerSortOrder;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
$payload = json_decode(File::get(base_path("database/data/fixes/batch493.json")), true, 512, JSON_THROW_ON_ERROR);
if (($payload["batch"] ?? null) !== 493 || ($payload["expected_count"] ?? null) !== 1 || count($payload["entries"] ?? []) !== 1) { throw new \RuntimeException("Unexpected batch identity or count."); }
$entry = $payload["entries"][0];
$dry = getenv("NPPC_BATCH_DRY_RUN") === "1";
$normalize = fn ($v) => trim(preg_replace("/[^a-z0-9]+/", " ", strtolower(Str::ascii((string) $v))));
$names = array_map($normalize, $entry["match_names"]);
if (! in_array($normalize($entry["prisoner"]["name"]), $names, true)) { throw new \RuntimeException("Invalid identity aliases."); }
Validator::make($entry, ["prisoner.name"=>"required|string|max:255", "prisoner.description"=>"required|string", "prisoner.website"=>"required|url", "prisoner.in_custody"=>"required|boolean|accepted", "prisoner.awaiting_trial"=>"required|boolean|accepted", "case.charges"=>"required|string|max:255", "case.arrest_date"=>"required|date_format:Y-m-d H:i:s|before_or_equal:now", "case.incarceration_date"=>"required|date_format:Y-m-d H:i:s|before_or_equal:now"])->validate();
if ($entry["case"]["arrest_date"] !== "2026-08-18 00:00:00" || $entry["case"]["incarceration_date"] !== "2026-08-18 00:00:00" || $entry["case"]["institution_id"] !== null || $entry["case"]["release_date"] !== null || $entry["case"]["convicted"] !== null || $entry["prisoner"]["birthdate"] !== null) { throw new \RuntimeException("Unexpected unsupported case or biography detail."); }
$result = DB::transaction(function () use ($entry, $names, $normalize, $dry) {
    $matches = Prisoner::withoutGlobalScopes()->lockForUpdate()->get(["id","name","first_name","middle_name","last_name","aka","slug"])->filter(function ($r) use ($names,$normalize) {
        foreach ([$r->name,$r->slug,$r->aka,$r->first_name." ".$r->last_name,$r->first_name." ".$r->middle_name." ".$r->last_name] as $value) {
            foreach ($names as $name) { if (str_contains(" ".$normalize($value)." "," ".$name." ")) { return true; } }
        }
        return false;
    });
    if ($matches->count() > 1) { throw new \RuntimeException("Ambiguous existing identity; no writes."); }
    if ($matches->isNotEmpty()) { echo "Preserved existing: ",$matches->first()->name,"\n"; return ["added"=>0,"id"=>$matches->first()->id]; }
    if ($dry) { echo "Would add: ",$entry["prisoner"]["name"]," with one ongoing military case\n"; return ["added"=>1,"id"=>null]; }
    $profileProof = [];
    foreach (DB::table("prisoners")->orderBy("id")->cursor() as $row) {
        $data = (array) $row; $sort = $data["sort_order"]; unset($data["sort_order"]);
        $profileProof[$row->id] = ["sort"=>$sort,"hash"=>hash("sha256",json_encode($data))];
    }
    $digest = function ($table,$excludeId) {
        $ctx = hash_init("sha256");
        foreach (DB::table($table)->where("id","!=",$excludeId)->orderBy("id")->cursor() as $row) { hash_update($ctx,json_encode($row)."\n"); }
        return hash_final($ctx);
    };
    $caseProof = $digest("prisoner_cases","");
    $dashboardProof = $digest("dashboard_links","");
    $record = Prisoner::create($entry["prisoner"]);
    $caseData = $entry["case"];
    $caseData["prisoner_id"] = $record->id;
    $case = PrisonerCase::create($caseData);
    $record->load("cases");
    $placement = PrisonerSortOrder::place($record,$record->era);
    echo "Sort placement: ",json_encode($placement),"\n";
    $verifiedProfiles = 0;
    foreach (DB::table("prisoners")->where("id","!=",$record->id)->orderBy("id")->cursor() as $row) {
        if (! isset($profileProof[$row->id])) { throw new \RuntimeException("Unexpected concurrent profile."); }
        $data = (array) $row; $sort = $data["sort_order"]; unset($data["sort_order"]); $old = $profileProof[$row->id];
        $expectedSort = (int) $old["sort"] + ((int) $old["sort"] > $placement["after"] ? 1 : 0);
        if (hash("sha256",json_encode($data)) !== $old["hash"] || (int) $sort !== $expectedSort) { throw new \RuntimeException("Existing profile changed beyond expected sort shift."); }
        $verifiedProfiles++;
    }
    if ($verifiedProfiles !== count($profileProof) || $caseProof !== $digest("prisoner_cases",$case->id) || $dashboardProof !== $digest("dashboard_links","")) { throw new \RuntimeException("Existing rows were not preserved."); }
    echo "Preservation verified: ",$verifiedProfiles," existing profiles; existing cases and dashboard unchanged\n";
    if ($record->cases()->count() !== 1 || ! $record->in_custody || ! $record->awaiting_trial || $record->released || $case->incarceration_date->format("Y-m-d") !== "2026-08-18") { throw new \RuntimeException("Creation verification failed."); }
    echo "Added profile: /prisoner/",$record->slug,"\n";
    return ["added"=>1,"id"=>$record->id];
});
if (! $dry && $result["added"] > 0) { Cache::forget(PrisonerApiController::cacheKey()); }
echo ($dry ? "Would add: " : "Added: "),$result["added"],"\n";
echo "B493-OK\n";
'
run "add-jason-watson" "B493-OK" "$ADD_CODE" || exit 1
echo "Batch 493 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
