#!/usr/bin/env bash
# Batch 831: user-selected Alfred Marder portrait; preserve existing dates.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-831.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch831.json")),true,512,JSON_THROW_ON_ERROR);
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";$disk=Illuminate\Support\Facades\Storage::disk("public");
$b=file_get_contents(base_path("database/data/photos/".$p["file"]));if(hash("sha256",$b)!==$p["sha256"]||!getimagesizefromstring($b)){throw new RuntimeException("Invalid image");}
Illuminate\Support\Facades\DB::transaction(function()use($p,$dry,$disk,$b){
$q=Illuminate\Support\Facades\DB::table("prisoners")->where("id",$p["expected"]["id"]);$r=(array)$q->first();$already=true;foreach($p["updates"] as $k=>$v){if($r[$k]!==$v){$already=false;}}
foreach($p["expected"] as $k=>$v){if($already&&($k==="updated_at"||array_key_exists($k,$p["updates"]))){continue;}if($r[$k]!==$v){throw new RuntimeException("Concurrent change: ".$k);}}
if($already){echo "Already applied\n";return;}if($dry){echo "Would update portrait and credit\n";return;}
$path=$p["updates"]["photo"];if($disk->exists($path)&&hash("sha256",$disk->get($path))!==$p["sha256"]){throw new RuntimeException("Asset collision");}
if(!$disk->put($path,$b,"public")){throw new RuntimeException("Copy failed");}$q->update($p["updates"]+["updated_at"=>now()->format("Y-m-d H:i:s")]);$after=(array)$q->first();foreach($r as $k=>$v){if($k!=="updated_at"&&$after[$k]!==($p["updates"][$k]??$v)){throw new RuntimeException("Preservation failure");}}echo "Updated portrait and credit\n";
});if(!$dry){Illuminate\Support\Facades\Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B831-OK\n";
'
run portrait B831-OK "$ADD_CODE" || exit 1
