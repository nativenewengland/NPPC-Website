#!/usr/bin/env bash
# Batch 632: verified missing dates in the 1960s case review.
# Guard full case snapshots; refine partial dates only within existing month/year.
# Preserve all biographies and unrelated fields; recalculate only selected case counters.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-632.sh [--dry-run]" >&2
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
$payload=json_decode(File::get(base_path("database/data/fixes/batch632.json")),true,512,JSON_THROW_ON_ERROR);
if($payload["batch"]!==632 || count($payload["entries"])!==3){throw new RuntimeException("Invalid batch");}
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
$same=fn($a,$b)=>$a===null||$b===null?$a===$b:(string)$a===(string)$b;
for($attempt=1;$attempt<=5;$attempt++){
try{
$count=DB::transaction(function()use($payload,$dry,$same){
 $plans=[];
 foreach($payload["entries"] as $e){
  $p=Prisoner::withoutGlobalScopes()->findOrFail($e["prisoner_id"]);
  if($p->name!==$e["name"]||$p->slug!==$e["slug"]){throw new RuntimeException("Identity changed");}
  $rows=DB::table("prisoner_cases")->where("prisoner_id",$p->id)->lockForUpdate()->get()->keyBy("id");
  $expectedIds=array_column($e["expected_cases"],"id");
  foreach($rows as $row){if(!in_array($row->id,$expectedIds,true)&&$row->id!==$e["case_id"]){throw new RuntimeException("New case requires duplicate review");}}
  foreach($e["expected_cases"] as $old){$row=(array)($rows[$old["id"]]??[]);foreach($old as $k=>$v){if(!array_key_exists($k,$row)||!$same($row[$k],$v)){throw new RuntimeException("Existing case changed");}}}
  $data=$e["case"];
  if($data["prisoner_id"]!==$p->id||$data["id"]!==$e["case_id"]){throw new RuntimeException("Invalid case owner");}
  if($data["incarceration_date"]!=="1960-08-17 00:00:00"||$data["release_date"]!=="1960-08-01 00:00:00"||$data["documented_imprisoned_for_days"]!==10){throw new RuntimeException("Invalid custody scope");}
  $candidate=new PrisonerCase();$candidate->forceFill($data);$candidate->setRelation("prisoner",$p);
  $days=$candidate->computeImprisonedForDays();
  if($days!==$e["expected_days"]){throw new RuntimeException("Unexpected duration");}
  $already=isset($rows[$e["case_id"]]);
  if($already){$existing=(array)$rows[$e["case_id"]];foreach($data as $k=>$v){if(is_array($v)){$v=json_encode($v);}if(!$same($existing[$k]??null,$v)){throw new RuntimeException("Replay case differs");}}if(!$same($existing["imprisoned_for_days"],$days)){throw new RuntimeException("Replay duration differs");}}
  $candidate->imprisoned_for_days=$days;$candidate->in_exile_for_days=null;
  $plans[]=["entry"=>$e,"candidate"=>$candidate,"already"=>$already];
 }
 $newIds=array_column($payload["entries"],"case_id");
 $digest=function($table,$exclude){$h=hash_init("sha256");foreach(DB::table($table)->whereNotIn("id",$exclude)->orderBy("id")->cursor() as $row){hash_update($h,json_encode($row)."\n");}return hash_final($h);};
 $beforeP=$digest("prisoners",[]);$beforeC=$digest("prisoner_cases",$newIds);$added=0;
 foreach($plans as $plan){if($plan["already"]){continue;}echo ($dry?"Would add case: ":"Adding case: "),$plan["entry"]["name"],"\n";if(!$dry){$plan["candidate"]->saveQuietly();} $added++;}
 if($beforeP!==$digest("prisoners",[])||$beforeC!==$digest("prisoner_cases",$newIds)){throw new RuntimeException("Preservation failed");}
 echo "All existing profiles and cases preserved\n";return $added;
});break;
}catch(\Illuminate\Database\QueryException $error){if($attempt>=5||!str_contains($error->getMessage(),"database is locked")){throw $error;}usleep(500000);}}
if(!$dry&&$count){Cache::forget(PrisonerApiController::cacheKey());}
echo ($dry?"Would add: ":"Added: "),$count,"\nB632-OK\n";
'
run "add-separate-1960s-custody-episodes" "B632-OK" "$ADD_CODE" || exit 1
echo "Batch 632 complete."
