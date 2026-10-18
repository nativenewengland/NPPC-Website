#!/usr/bin/env bash
# Remove requested affiliations only; preserve all cases and other profile fields.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-1142.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch1142.json")),true,512,JSON_THROW_ON_ERROR);
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoners")->whereRaw("0=1")->update(["affiliation"=>null]);}
$digest=function($table,$omit=false){$h=hash_init("sha256");foreach(DB::table($table)->orderBy("id")->cursor() as $row){$r=(array)$row;if($omit){unset($r["affiliation"]);}hash_update($h,json_encode($r,JSON_UNESCAPED_UNICODE)."\n");}return hash_final($h);};
$before=$digest("prisoners",true);$cases=$digest("prisoner_cases");$changed=0;$already=0;$ids=[];
foreach($p["records"] as $e){if(isset($ids[$e["id"]])){throw new RuntimeException("Duplicate ID");}$ids[$e["id"]]=true;$r=DB::table("prisoners")->where("id",$e["id"])->first();if(!$r||$r->name!==$e["name"]){throw new RuntimeException("Identity changed");}if($r->affiliation===$e["after"]){$already++;continue;}if($r->affiliation!==$e["before"]){throw new RuntimeException("Affiliations changed: ".$e["name"]);}if(!$dry){DB::table("prisoners")->where("id",$e["id"])->update(["affiliation"=>$e["after"]]);}echo ($dry?"Would update: ":"Updated: "),$e["name"],"\n";$changed++;}
if($before!==$digest("prisoners",true)||$cases!==$digest("prisoner_cases")){throw new RuntimeException("Unrelated fields changed; rollback");}
echo "Updated profiles: ",$changed,"; already applied: ",$already,"\n";
echo "All case fields and other profile fields preserved\n";
});
if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());Cache::forget("icons.options.v1");}
echo "B1142-OK\n";
'
run remove-requested-affiliations B1142-OK "$ADD_CODE" || exit 1
