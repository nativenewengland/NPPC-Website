#!/usr/bin/env bash
# Batch1261: Source-verified custody intervals and sentence corrections.
# Guard full live snapshots and preserve fields outside the reviewed corrections.
# Retain source precision and exclude documented releases on bail from custody intervals.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-1261.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch1261.json")),true,512,JSON_THROW_ON_ERROR);$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
$check=function($a,$b){foreach($b as $k=>$v){if(!array_key_exists($k,$a)||$a[$k]!==$v){throw new RuntimeException("Snapshot mismatch: ".$k);}}};
$raw=(array)DB::table("prisoners")->where("id",$p["prisoner"]["id"])->first();$check($raw,$p["prisoner"]);
foreach($p["existing_cases"] as $c){$check((array)DB::table("prisoner_cases")->where("id",$c["id"])->first(),$c);}
$n=$p["new_case"];$existing=DB::table("prisoner_cases")->where("id",$n["id"])->first();$count=DB::table("prisoner_cases")->where("prisoner_id",$n["prisoner_id"])->count();
if($existing){$e=(array)$existing;unset($n["created_at"],$n["updated_at"]);$check($e,$n);if($count!==count($p["existing_cases"])+1){throw new RuntimeException("Unexpected case graph");}echo "Already applied: William Duane separate contempt case\n";return;}
if($count!==count($p["existing_cases"])){throw new RuntimeException("Concurrent case addition");}
if(DB::table("prisoner_cases")->where("prisoner_id",$n["prisoner_id"])->where("charges",$n["charges"])->exists()){throw new RuntimeException("Duplicate contempt case");}
if($dry){echo "Would add: William Duane separate contempt case\n";return;}
$n["created_at"]=now()->format("Y-m-d H:i:s");$n["updated_at"]=$n["created_at"];DB::table("prisoner_cases")->insert($n);$check((array)DB::table("prisoner_cases")->where("id",$n["id"])->first(),$n);$check((array)DB::table("prisoners")->where("id",$p["prisoner"]["id"])->first(),$p["prisoner"]);echo "Added: William Duane separate contempt case\n";
});if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B1261-OK\n";

'
run correct-custody-sentence-audit-fourth-pass B1261-OK "$ADD_CODE" || exit 1
