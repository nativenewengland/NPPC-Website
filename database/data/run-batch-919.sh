#!/usr/bin/env bash
# Batch 919: nine historical peace-protest custody candidates from retained PDF evidence.
# Includes under-review records in identity preflight; replay preserves existing records.
# Uses normal curated sort placement; no existing biographies or cases are changed.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-919.sh [--dry-run]" >&2
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch919.json")),true,512,JSON_THROW_ON_ERROR);
if($p["batch"]!==919||count($p["entries"])!==0){throw new RuntimeException("Batch identity mismatch");}
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
$norm=fn($v)=>trim(preg_replace("/[^a-z0-9]+/"," ",strtolower(Str::ascii((string)$v))));
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry,$norm){
if(!$dry){DB::table("prisoners")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["entries"] as $e){
$names=array_map($norm,$e["match_names"]);
$matches=Prisoner::withoutGlobalScopes()->get()->filter(function($r)use($names,$norm){foreach([$r->name,$r->slug,$r->aka,$r->first_name." ".$r->last_name,$r->first_name." ".$r->middle_name." ".$r->last_name] as $v){foreach($names as $n){if(str_contains(" ".$norm($v)." "," ".$n." ")){return true;}}}return false;});
if($matches->count()>1){throw new RuntimeException("Ambiguous identity");}
if($matches->count()){echo "Preserved existing: ",$matches->first()->name,"\n";continue;}
if($dry){echo "Would add: ",$e["prisoner"]["name"],"\n";continue;}
$proof=[];foreach(DB::table("prisoners")->orderBy("id")->cursor() as $row){$a=(array)$row;$sort=$a["sort_order"];unset($a["sort_order"]);$proof[$row->id]=[$sort,hash("sha256",json_encode($a))];}
$digest=function($exclude){$h=hash_init("sha256");foreach(DB::table("prisoner_cases")->where("id","!=",$exclude)->orderBy("id")->cursor() as $r){hash_update($h,json_encode($r)."\n");}return hash_final($h);};
$before=$digest("");$record=Prisoner::create($e["prisoner"]);$case=PrisonerCase::create($e["case"]+["prisoner_id"=>$record->id]);$record->load("cases");$placement=App\Support\PrisonerSortOrder::place($record,$record->era);
foreach(DB::table("prisoners")->where("id","!=",$record->id)->orderBy("id")->cursor() as $row){$a=(array)$row;$sort=$a["sort_order"];unset($a["sort_order"]);$old=$proof[$row->id];$expected=(int)$old[0]+((int)$old[0]>$placement["after"]?1:0);if($expected!==(int)$sort||hash("sha256",json_encode($a))!==$old[1]){throw new RuntimeException("Existing profile changed");}}
if($before!==$digest($case->id)||$record->cases()->count()!==1){throw new RuntimeException("Case preservation failed");}
foreach($e["case"] as $k=>$v){$got=$case->getRawOriginal($k);if($k==="date_precision"){$got=json_decode($got,true);}if($got!==$v){throw new RuntimeException("Creation verification failed: ".$k);}}
echo "Added: ",$record->name,"; existing profile and case preservation verified\n";
}
});
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);}
foreach($p["case_additions"] as $e){$id=$e["expected_prisoner"]["id"];$profile=(array)DB::table("prisoners")->where("id",$id)->first();
foreach($e["expected_prisoner"] as $k=>$v){if($k!=="sort_order"&&$profile[$k]!==$v){throw new RuntimeException("Existing profile changed: ".$k);}}
$existing=DB::table("prisoner_cases")->where("prisoner_id",$id)->where("charges",$e["case"]["charges"])->get();
if(count($existing)>1){throw new RuntimeException("Duplicate case match");}
if(count($existing)){foreach($e["case"] as $k=>$v){$got=$existing[0]->$k;if($k==="date_precision"){$got=json_decode($got,true);}if($got!==$v){throw new RuntimeException("Existing case differs");}}echo "Preserved existing case: ",$e["name"],"\n";continue;}
$current=DB::table("prisoner_cases")->where("prisoner_id",$id)->orderBy("id")->get()->map(fn($v)=>(array)$v)->all();$expected=$e["expected_cases"];usort($expected,fn($a,$b)=>strcmp($a["id"],$b["id"]));if($current!==$expected){throw new RuntimeException("Case list changed");}
if($dry){echo "Would add historical case: ",$e["name"],"\n";continue;}
$before=[];foreach(DB::table("prisoner_cases")->orderBy("id")->cursor() as $row){$before[$row->id]=hash("sha256",json_encode($row));}
$c=PrisonerCase::create($e["case"]+["prisoner_id"=>$id]);
foreach(DB::table("prisoner_cases")->where("id","!=",$c->id)->orderBy("id")->cursor() as $row){if(!isset($before[$row->id])||$before[$row->id]!==hash("sha256",json_encode($row))){throw new RuntimeException("Existing case modified");}}
if((array)DB::table("prisoners")->where("id",$id)->first()!==$profile){throw new RuntimeException("Profile preservation failed");}
foreach($e["case"] as $k=>$v){$got=$c->getRawOriginal($k);if($k==="date_precision"){$got=json_decode($got,true);}if($got!==$v){throw new RuntimeException("New case verification failed");}}
echo "Added historical case: ",$e["name"],"; prior case and profile preserved\n";
}
});
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
});
if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B919-OK\n";
'
run add-verified-pdf-prisoners B919-OK "$ADD_CODE" || exit 1
