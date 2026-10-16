#!/usr/bin/env bash
# Batch 829: verified missing dates in the random-sample case review.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-829.sh [--dry-run]" >&2
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
$p=json_decode(File::get(base_path("database/data/fixes/batch829.json")),true,512,JSON_THROW_ON_ERROR);
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["entries"] as $e){
$before=(array)DB::table("prisoners")->where("id",$e["prisoner"]["id"])->first();
if($before!==$e["prisoner"]){throw new \RuntimeException("Profile changed: ".$e["name"]);}
$new=isset($e["create"]);$data=$new?$e["create"]:$e["updates"];$id=$new?$data["id"]:$e["case_id"];
$raw=DB::table("prisoner_cases")->where("id",$id)->first();
$model=$new?new PrisonerCase:PrisonerCase::findOrFail($id);$model->forceFill($data);$data["imprisoned_for_days"]=$model->computeImprisonedForDays();
$data["date_precision"]=json_encode($data["date_precision"],JSON_UNESCAPED_SLASHES);
$already=$raw!==null;foreach($data as $k=>$v){if(!$raw||$raw->$k!==$v){$already=false;}}
$expectedIds=array_column($e["existing_cases"],"id");$actualIds=DB::table("prisoner_cases")->where("prisoner_id",$e["prisoner"]["id"])->pluck("id")->all();if($new&&$already){$expectedIds[]=$id;}sort($expectedIds);sort($actualIds);if($expectedIds!==$actualIds){throw new \RuntimeException("Case membership changed");}
foreach($e["existing_cases"] as $old){$now=(array)DB::table("prisoner_cases")->where("id",$old["id"])->first();foreach($old as $k=>$v){if($already&&!$new&&$old["id"]===$id&&(array_key_exists($k,$data)||$k==="updated_at")){continue;}if($now[$k]!==$v){throw new \RuntimeException("Case changed: ".$e["name"]." / ".$k);}}}
if($already){echo "Already applied: ",$e["name"],"\n";continue;}
if(!$dry){if($new){DB::table("prisoner_cases")->insert($data+["created_at"=>now()->format("Y-m-d H:i:s"),"updated_at"=>now()->format("Y-m-d H:i:s")]);}else{DB::table("prisoner_cases")->where("id",$id)->update($data+["updated_at"=>now()->format("Y-m-d H:i:s")]);}$after=(array)DB::table("prisoner_cases")->where("id",$id)->first();foreach($data as $k=>$v){if($after[$k]!==$v){throw new \RuntimeException("Verification failed: ".$k);}}if((array)DB::table("prisoners")->where("id",$before["id"])->first()!==$before){throw new \RuntimeException("Profile preservation failed");}}
echo ($dry?"Would apply: ":"Applied: "),$e["name"],"\n";
}
});
if(!$dry){Cache::forget(PrisonerApiController::cacheKey());}
echo "B829-OK\n";
'
run "resolve-custody-episodes" "B829-OK" "$ADD_CODE" || exit 1
