#!/usr/bin/env bash
# Batch 904: separate the conflated George Ryan identities with full row guards.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-904.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch904.json")),true,512,JSON_THROW_ON_ERROR);$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
$fetch=function($t,$id){$r=DB::table($t)->where("id",$id)->first();if(!$r){throw new RuntimeException("Missing expected row");}return (array)$r;};
$equal=function($a,$b){foreach($b as $k=>$v){if(in_array($k,["updated_at","created_at"])){continue;}if(!array_key_exists($k,$a)||$a[$k]!==$v){throw new RuntimeException("Snapshot mismatch: ".$k);}}};
$old=$fetch("prisoners",$p["old"]["id"]);$ic=$fetch("prisoner_cases",$p["iww_case"]["id"]);$cc=$fetch("prisoner_cases",$p["civil_case"]["id"]);$new=DB::table("prisoners")->where("id",$p["new"]["id"])->first();
$oldWant=array_replace($p["old"],$p["updates"]);$iwwWant=array_replace($p["iww_case"],$p["iww_updates"]);$civilWant=array_replace($p["civil_case"],["prisoner_id"=>$p["new"]["id"]]);
foreach($p["unrelated_ryans"]as $r){$equal($fetch("prisoners",$r["id"]),$r);}
foreach(["calendar_entries","podcast_episodes"]as $t){if(DB::table($t)->whereIn("prisoner_id",[$old["id"],$p["new"]["id"]])->exists()){throw new RuntimeException("Unexpected linked content");}}
if($new){$equal($old,$oldWant);$equal($ic,$iwwWant);$equal($cc,$civilWant);$equal((array)$new,$p["new"]);if(DB::table("prisoner_cases")->where("prisoner_id",$old["id"])->count()!==1||DB::table("prisoner_cases")->where("prisoner_id",$p["new"]["id"])->count()!==1){throw new RuntimeException("Unexpected case graph");}echo "Already applied: George Ryan identity split\n";return;}
$equal($old,$p["old"]);$equal($ic,$p["iww_case"]);$equal($cc,$p["civil_case"]);
if(DB::table("prisoners")->where("slug",$p["new"]["slug"])->exists()||DB::table("prisoners")->where("name",$p["new"]["name"])->exists()){throw new RuntimeException("New identity collision");}
if(DB::table("prisoner_cases")->where("prisoner_id",$old["id"])->count()!==2){throw new RuntimeException("Unexpected original case graph");}
$pc=DB::table("prisoners")->count();$ccount=DB::table("prisoner_cases")->count();
if($dry){echo "Would split: George Ryan IWW / George W. Ryan Civil War\n";return;}
$stamp=now()->format("Y-m-d H:i:s");$n=$p["new"];$n["created_at"]=$stamp;$n["updated_at"]=$stamp;DB::table("prisoners")->insert($n);
DB::table("prisoners")->where("id",$old["id"])->update($p["updates"]+["updated_at"=>$stamp]);
DB::table("prisoner_cases")->where("id",$ic["id"])->update($p["iww_updates"]+["updated_at"=>$stamp]);
DB::table("prisoner_cases")->where("id",$cc["id"])->update(["prisoner_id"=>$n["id"],"updated_at"=>$stamp]);
$equal($fetch("prisoners",$old["id"]),$oldWant);$equal($fetch("prisoners",$n["id"]),$p["new"]);$equal($fetch("prisoner_cases",$ic["id"]),$iwwWant);$equal($fetch("prisoner_cases",$cc["id"]),$civilWant);
if(DB::table("prisoners")->count()!==$pc+1||DB::table("prisoner_cases")->count()!==$ccount){throw new RuntimeException("Count preservation failed");}
foreach($p["unrelated_ryans"]as $r){$equal($fetch("prisoners",$r["id"]),$r);}
echo "Applied: two identities; two original case IDs preserved; no cases deleted\n";
});if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B904-OK\n";
'
run split-george-ryan-identities B904-OK "$ADD_CODE" || exit 1
