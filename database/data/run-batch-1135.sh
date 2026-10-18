#!/usr/bin/env bash
# Batch1135: Clear explicitly authorized non-charge descriptions; preserve all other fields.
# Remove only explicitly named appended charges/verdict paragraphs.
# Preserve all other fields, including notes, dates, charges, and timestamps.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-1135.sh [--dry-run]" >&2
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
$p=json_decode(file_get_contents(base_path("database/data/fixes/batch1135.json")),true,512,JSON_THROW_ON_ERROR);
if($p["batch"]!==1135||count($p["records"])!==$p["count"]){throw new RuntimeException("Payload count mismatch");}
$dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p,$dry){
if(!$dry){DB::table("prisoner_cases")->whereRaw("0=1")->update(["sentence"=>null]);}
$changes=[];$seen=[];$already=0;
foreach($p["records"] as $e){if(isset($seen[$e["id"]])){throw new RuntimeException("Duplicate payload ID");}$seen[$e["id"]]=true;$r=DB::table("prisoner_cases")->where("id",$e["id"])->first();if(!$r||$r->prisoner_id!==$e["prisoner_id"]){throw new RuntimeException("Case identity changed");}if($r->sentence===$e["after"]){$already++;continue;}if($r->sentence!==$e["before"]){throw new RuntimeException("Concurrent sentence edit: ".$e["id"]);}if($e["after"]!==null&&(!is_string($e["after"])||trim($e["after"])==="")){throw new RuntimeException("Invalid target sentence");}$changes[]=$e;}
$digest=function($table,$excludeSentence=false){$h=hash_init("sha256");foreach(DB::table($table)->orderBy("id")->cursor() as $row){$r=(array)$row;if($excludeSentence){unset($r["sentence"]);}hash_update($h,json_encode($r,JSON_UNESCAPED_UNICODE)."\n");}return hash_final($h);};
$cases=$digest("prisoner_cases",true);$profiles=$digest("prisoners");
$untouched=function()use($seen){$h=hash_init("sha256");foreach(DB::table("prisoner_cases")->orderBy("id")->cursor() as $row){if(!isset($seen[$row->id])){hash_update($h,json_encode($row)."\n");}}return hash_final($h);};$other=$untouched();
if(!$dry){foreach($changes as $e){DB::table("prisoner_cases")->where("id",$e["id"])->update(["sentence"=>$e["after"]]);}foreach($p["records"] as $e){if(DB::table("prisoner_cases")->where("id",$e["id"])->value("sentence")!==$e["after"]){throw new RuntimeException("Write verification failed");}}}
if($cases!==$digest("prisoner_cases",true)||$profiles!==$digest("prisoners")||$other!==$untouched()){throw new RuntimeException("Unrelated fields changed; rolling back");}
echo ($dry?"Would update: ":"Updated: "),count($changes)," case sentence\n";echo "Already applied: ",$already,"\n";echo "Every case note, date, timestamp, other case field and profile preserved\n";
});
if(!$dry){Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());Cache::forget("icons.options.v1");}
echo "B1135-OK\n";
'
run sentence-only-cleanup B1135-OK "$ADD_CODE" || exit 1
