#!/usr/bin/env bash
# Batch 828: verified missing dates in the random-sample case review.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-828.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
$p=json_decode(File::get(base_path("database/data/fixes/batch828.json")),true,512,JSON_THROW_ON_ERROR);
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["entries"] as $p){
$case=PrisonerCase::findOrFail($p["case"]["id"]);
if($p["case_updates"]){
$case->release_date=$p["case_updates"]["release_date"];
$case->date_precision=json_decode($p["case_updates"]["date_precision"],true);
$updates=$p["case_updates"];
$updates["imprisoned_for_days"]=$case->computeImprisonedForDays();
}else{$updates=[];}
foreach([["prisoners","prisoner",$p["profile_updates"]],["prisoner_cases","case",$updates]] as [$table,$key,$changes]){
if(!$changes){continue;}
$before=(array)DB::table($table)->where("id",$p[$key]["id"])->first();
$already=true;foreach($changes as $k=>$v){if($before[$k]!==$v){$already=false;}}
foreach($p[$key] as $k=>$v){if($already&&(array_key_exists($k,$changes)||$k==="updated_at")){continue;}if($before[$k]!==$v){throw new \RuntimeException("Concurrent change: ".$table." / ".$k);}}
if($already){echo "Already applied: ",$table,"\n";continue;}
if(!$dry){DB::table($table)->where("id",$p[$key]["id"])->update($changes+["updated_at"=>now()->format("Y-m-d H:i:s")]);$after=(array)DB::table($table)->where("id",$p[$key]["id"])->first();foreach($before as $k=>$v){if(!array_key_exists($k,$changes)&&$k!=="updated_at"&&$after[$k]!==$v){throw new \RuntimeException("Preservation failed");}}foreach($changes as $k=>$v){if($after[$k]!==$v){throw new \RuntimeException("Write verification failed");}}}
echo ($dry?"Would update: ":"Updated: "),$table,"\n";
}
}
});
if(!$dry){Cache::forget(PrisonerApiController::cacheKey());}
echo "B828-OK\n";
'
run "verify-beisswenger-release" "B828-OK" "$ADD_CODE" || exit 1
