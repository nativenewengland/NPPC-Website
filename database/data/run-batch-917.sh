#!/usr/bin/env bash
# Batch 917: two verified missing draft resisters from retained PDF evidence.
# Includes under-review records in identity preflight; replay preserves existing records.
# Uses normal curated sort placement; no existing biographies or cases are changed.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-917.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch917.json")),true,512,JSON_THROW_ON_ERROR);
if($p["batch"]!==917||count($p["entries"])!==2){throw new RuntimeException("Batch identity mismatch");}
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
});if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());}echo "B917-OK\n";
'
run add-verified-pdf-prisoners B917-OK "$ADD_CODE" || exit 1
