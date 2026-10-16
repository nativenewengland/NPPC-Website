#!/usr/bin/env bash
# Batch 841: twelve verified missing case fields on five profiles.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-841.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\Cache;
use App\Models\PrisonerCase;
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch841.json")),true,512,JSON_THROW_ON_ERROR);$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["operations"] as $e){$table=$e["table"];$id=$e["expected"]["id"];$raw=(array)DB::table($table)->where("id",$id)->first();$changes=$e["updates"];
if($table==="prisoner_cases"&&(array_key_exists("release_date",$changes)||array_key_exists("incarceration_date",$changes))){$model=PrisonerCase::findOrFail($id);foreach($changes as $k=>$v){$model->$k=$k==="date_precision"?json_decode($v,true):$v;}$changes["imprisoned_for_days"]=$model->computeImprisonedForDays();}
$disk=Illuminate\Support\Facades\Storage::disk("public");if(isset($e["file"])){$b=file_get_contents(base_path("database/data/photos/".$e["file"]));if(hash("sha256",$b)!==$e["sha256"]||!getimagesizefromstring($b)){throw new RuntimeException("Invalid image");}if($disk->exists($changes["photo"])&&hash("sha256",$disk->get($changes["photo"]))!==$e["sha256"]){throw new RuntimeException("Asset collision");}}
$already=true;foreach($changes as $k=>$v){if($raw[$k]!==$v){$already=false;}}
foreach($e["expected"] as $k=>$v){if($already&&(array_key_exists($k,$changes)||$k==="updated_at")){continue;}if($raw[$k]!==$v){throw new RuntimeException("Concurrent change: ".$id." ".$k);}}
if($already){echo "Already applied: ",$e["name"],"\n";continue;}
if(!$dry){if(isset($e["file"])&&!$disk->put($changes["photo"],$b,"public")){throw new RuntimeException("Photo copy failed");}DB::table($table)->where("id",$id)->update($changes+["updated_at"=>now()->format("Y-m-d H:i:s")]);$after=(array)DB::table($table)->where("id",$id)->first();foreach($raw as $k=>$v){$want=array_key_exists($k,$changes)?$changes[$k]:$v;if($k!=="updated_at"&&$after[$k]!==$want){throw new RuntimeException("Preservation/write check failed");}}}
echo ($dry?"Would update: ":"Updated: "),$e["name"],"\n";
}
});if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B841-OK\n";
'
run round24-verified-fields B841-OK "$ADD_CODE" || exit 1
