#!/usr/bin/env bash
# Batch1223: Source-verified custody intervals and sentence corrections.
# Guard full live snapshots and preserve fields outside the reviewed corrections.
# Retain source precision and exclude documented releases on bail from custody intervals.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-1223.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch1223.json")),true,512,JSON_THROW_ON_ERROR);$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
$np=$p["new_profile"];$nc=$p["new_case"];
$matches=DB::table("prisoners")->where("id",$np["id"])->orWhere("slug",$np["slug"])->orWhereRaw("lower(name) like ?",["%schock%"])->get();
$check=function($table,$want){$got=(array)DB::table($table)->where("id",$want["id"])->first();foreach($want as $k=>$v){if(!array_key_exists($k,$got)||$got[$k]!==$v){throw new RuntimeException("Inserted/replay field mismatch: ".$k);}}};
if($matches->count()){
if($matches->count()!==1||$matches[0]->id!==$np["id"]){throw new RuntimeException("Identity collision requires review");}
$check("prisoners",$np);$check("prisoner_cases",$nc);if(DB::table("prisoner_cases")->where("prisoner_id",$np["id"])->count()!==1){throw new RuntimeException("Unexpected case graph");}echo "Already added: Joseph Edwin Schock\n";return;
}
if(DB::table("prisoner_cases")->where("id",$nc["id"])->exists()){throw new RuntimeException("Case identity collision");}
if($dry){echo "Would add: Joseph Edwin Schock\n";return;}
$counts=[DB::table("prisoners")->count(),DB::table("prisoner_cases")->count()];
$before=[];foreach(["prisoners","prisoner_cases"]as $t){$before[$t]=DB::table($t)->orderBy("id")->get()->map(fn($r)=>hash("sha256",json_encode($r)))->all();}
$stamp=now()->format("Y-m-d H:i:s");DB::table("prisoners")->insert($np+["sort_order"=>(DB::table("prisoners")->max("sort_order")??0)+10,"created_at"=>$stamp,"updated_at"=>$stamp]);DB::table("prisoner_cases")->insert($nc+["created_at"=>$stamp,"updated_at"=>$stamp]);
$check("prisoners",$np);$check("prisoner_cases",$nc);
foreach(["prisoners"=>$np["id"],"prisoner_cases"=>$nc["id"]]as $t=>$id){$after=DB::table($t)->where("id","!=",$id)->orderBy("id")->get()->map(fn($r)=>hash("sha256",json_encode($r)))->all();if($before[$t]!==$after){throw new RuntimeException("Unrelated rows changed");}}
if(DB::table("prisoners")->count()!==$counts[0]+1||DB::table("prisoner_cases")->count()!==$counts[1]+1){throw new RuntimeException("Count mismatch");}
echo "Added: Joseph Edwin Schock; all prior rows preserved\n";
});if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B1223-OK\n";
'
run add-schock-exile B1223-OK "$ADD_CODE" || exit 1
