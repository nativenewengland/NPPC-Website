#!/usr/bin/env bash
# Batch 697: verified missing dates in the 1960s case review.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-697.sh [--dry-run]" >&2
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
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
$payload=json_decode(File::get(base_path("database/data/fixes/batch697.json")),true,512,JSON_THROW_ON_ERROR);
if($payload["batch"]!==697||$payload["expected_count"]!==1||count($payload["entries"])!==1){throw new RuntimeException("Unexpected payload");}
$e=$payload["entries"][0];$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
$same=fn($a,$b)=>$a===null||$b===null?$a===$b:(string)$a===(string)$b;
$result=DB::transaction(function()use($e,$dry,$same){
$p=Prisoner::withoutGlobalScopes()->findOrFail($e["prisoner_id"]);
if($p->name!==$e["name"]||$p->slug!==$e["slug"]){throw new RuntimeException("Identity mismatch");}
$c=$e["new_case"];
if($c["prisoner_id"]!==$p->id||$c["id"]!==$e["case_id"]||$c["imprisoned_for_months"]!==14){throw new RuntimeException("Invalid case");}
$old=DB::table("prisoner_cases")->where("prisoner_id",$p->id)->where("id","!=",$c["id"])->get();
if($old->count()!==count($e["expected_cases"])){throw new RuntimeException("Case list changed; check duplicates");}
foreach($e["expected_cases"] as $expected){$row=$old->firstWhere("id",$expected["id"]);if(!$row){throw new RuntimeException("Existing case missing");}foreach($expected as $k=>$v){if(!$same($row->$k,$v)){throw new RuntimeException("Existing case changed");}}}
$existing=DB::table("prisoner_cases")->where("id",$c["id"])->first();
if($existing){foreach($c as $k=>$v){if(in_array($k,["created_at","updated_at","imprisoned_for_days"],true)){continue;}if(!$same($existing->$k,$v)){throw new RuntimeException("Added case changed");}}echo "Already applied: ",$p->name,"\n";return 0;}
if($dry){echo "Would add: ",$p->name," Michigan case\n";return 1;}
$digest=function($table,$exclude){$ctx=hash_init("sha256");foreach(DB::table($table)->where("id","!=",$exclude)->orderBy("id")->cursor() as $row){hash_update($ctx,json_encode($row)."\n");}return hash_final($ctx);};
$profiles=$digest("prisoners","");$cases=$digest("prisoner_cases",$c["id"]);
$m=new PrisonerCase();foreach($c as $k=>$v){if(in_array($k,["created_at","updated_at","imprisoned_for_days"],true)){continue;}$m->setAttribute($k,$k==="date_precision"?json_decode($v,true):$v);}$m->setRelation("prisoner",$p);$c["imprisoned_for_days"]=$m->computeImprisonedForDays();$c["created_at"]=now()->format("Y-m-d H:i:s");$c["updated_at"]=$c["created_at"];
DB::table("prisoner_cases")->insert($c);
if($profiles!==$digest("prisoners","")||$cases!==$digest("prisoner_cases",$c["id"])){throw new RuntimeException("Unrelated data changed");}
echo "Preservation verified: profiles and existing cases unchanged\n";return 1;
});
if(!$dry&&$result){Cache::forget(PrisonerApiController::cacheKey());}
echo ($dry?"Would add: ":"Added: "),$result,"\nB697-OK\n";

'
run "correct-1960s-custody-dates" "B697-OK" "$ADD_CODE" || exit 1
echo "Batch 697 complete."
