#!/usr/bin/env bash
# Batch 830: verified missing dates in the random-sample case review.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-830.sh [--dry-run]" >&2
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
$p=json_decode(File::get(base_path("database/data/fixes/batch830.json")),true,512,JSON_THROW_ON_ERROR);$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["operations"] as $e){$table=$e["table"];$id=$e["expected"]["id"];$raw=(array)DB::table($table)->where("id",$id)->first();$changes=$e["updates"];
if($table==="prisoner_cases"){$model=PrisonerCase::findOrFail($id);$model->release_date=$changes["release_date"];$model->date_precision=json_decode($changes["date_precision"],true);$changes["imprisoned_for_days"]=$model->computeImprisonedForDays();}
$already=true;foreach($changes as $k=>$v){if($raw[$k]!==$v){$already=false;}}
foreach($e["expected"] as $k=>$v){if($already&&(array_key_exists($k,$changes)||$k==="updated_at")){continue;}if($raw[$k]!==$v){throw new \RuntimeException("Concurrent change: ".$id." ".$k);}}
if($already){echo "Already applied: ",$id,"\n";continue;}
if(!$dry){DB::table($table)->where("id",$id)->update($changes+["updated_at"=>now()->format("Y-m-d H:i:s")]);$after=(array)DB::table($table)->where("id",$id)->first();foreach($raw as $k=>$v){$want=array_key_exists($k,$changes)?$changes[$k]:$v;if($k!=="updated_at"&&$after[$k]!==$want){throw new \RuntimeException("Preservation/write check failed");}}}
echo ($dry?"Would update: ":"Updated: "),$id,"\n";
}
$np=$p["new_profile"];$nc=$p["new_case"];
$found=DB::table("prisoners")->where("slug",$np["slug"])->orWhere("inmate_number",$np["inmate_number"])->orWhere("name",$np["name"])->get();
if($found->count()){
if($found->count()!==1||$found[0]->id!==$np["id"]){throw new \RuntimeException("Duplicate identity requires review");}
foreach([["prisoners",$np],["prisoner_cases",$nc]] as [$table,$fields]){$raw=(array)DB::table($table)->where("id",$fields["id"])->first();foreach($fields as $k=>$v){if(($raw[$k]??null)!==$v){throw new \RuntimeException("Replay changed field");}}}echo "Already added: Nestor de Jesus Guishard\n";
}else{
if(!$dry){$stamp=now()->format("Y-m-d H:i:s");DB::table("prisoners")->insert($np+["sort_order"=>(DB::table("prisoners")->max("sort_order")??0)+10,"created_at"=>$stamp,"updated_at"=>$stamp]);DB::table("prisoner_cases")->insert($nc+["created_at"=>$stamp,"updated_at"=>$stamp]);}
echo ($dry?"Would add: ":"Added: "),"Nestor de Jesus Guishard\n";
}
});if(!$dry){Cache::forget(PrisonerApiController::cacheKey());}echo "B830-OK\n";
'
run "dublin-site-verified-additions" "B830-OK" "$ADD_CODE" || exit 1
