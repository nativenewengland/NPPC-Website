#!/usr/bin/env bash
# Batch 494: publish a sourced NPPC news article about Jason Watson.
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1

export NPPC_BATCH_DRY_RUN=0
if [[ $# -eq 1 && "$1" == "--dry-run" ]]; then
    export NPPC_BATCH_DRY_RUN=1
elif [[ $# -ne 0 ]]; then
    echo "Usage: bash database/data/run-batch-494.sh [--dry-run]" >&2
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
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Prisoner;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
$p=json_decode(File::get(base_path("database/data/fixes/batch494.json")),true,512,JSON_THROW_ON_ERROR);
if (($p["batch"]??null)!==494 || ($p["expected_count"]??null)!==1) { throw new \RuntimeException("Unexpected payload."); }
$a=$p["article"]; $dry=getenv("NPPC_BATCH_DRY_RUN")==="1";
Validator::make($a,["slug"=>"required|regex:/^[a-z0-9-]+$/","title"=>"required|string|max:255","intro"=>"required|string","body"=>"required|string","published_at"=>"required|date_format:Y-m-d H:i:s|before_or_equal:now","citations_json"=>"required|array|min:8","author_id"=>"required|uuid","category_id"=>"required|uuid"])->validate();
if(array_diff(array_keys($a),["slug","title","intro","body","author_id","category_id","published_at","image","image_caption","citations_json"])) { throw new \RuntimeException("Unsupported article field."); }
$asset=$p["asset"]; $bytes=File::get(base_path("database/data/photos/".$asset["file"])); $size=getimagesizefromstring($bytes);
if(! $size || $size[2]!==IMAGETYPE_JPEG || hash("sha256",$bytes)!==$asset["sha256"] || $asset["path"]!==$a["image"] || ! preg_match("#^articles/covers/[a-z0-9-]+[.]jpg$#",$asset["path"])) { throw new \RuntimeException("Invalid article image."); }
$disk=Storage::disk("public");
if($disk->exists($asset["path"]) && hash("sha256",$disk->get($asset["path"]))!==$asset["sha256"]) { throw new \RuntimeException("Existing image differs."); }
$result=DB::transaction(function()use($p,$a,$dry,$asset,$bytes,$disk){
    $author=Author::whereKey($p["author_expected"]["id"])->sole(); $category=Category::whereKey($p["category_expected"]["id"])->sole();
    if($author->name!==$p["author_expected"]["name"] || $author->slug!==$p["author_expected"]["slug"] || $category->title!==$p["category_expected"]["title"] || $category->slug!=="news" || $a["author_id"]!==$author->id || $a["category_id"]!==$category->id) { throw new \RuntimeException("Author/category guard failed."); }
    if(!Prisoner::withoutGlobalScopes()->whereKey($p["prisoner_profile_expected"]["id"])->where("slug",$p["prisoner_profile_expected"]["slug"])->exists()) { throw new \RuntimeException("Related prisoner profile missing."); }
    $matches=Article::where("slug",$a["slug"])->orWhere("title",$a["title"])->lockForUpdate()->get();
    if($matches->count()>1) { throw new \RuntimeException("Duplicate article identity."); }
    if($matches->isNotEmpty()) {
        $record=$matches->first();
        foreach($a as $field=>$value){$actual=$record->{$field};if($field==="published_at"){$actual=$actual?->format("Y-m-d H:i:s");}if($actual!==$value){throw new \RuntimeException("Existing article differs: ".$field);}}
        if(!$disk->exists($asset["path"])) { throw new \RuntimeException("Existing article image missing."); }
        echo "Existing article verified: ",$record->url,"\n";return 0;
    }
    if($dry) { echo "Would publish: ",$a["title"],"\n";return 1; }
    $digest=function($table,$exclude=""){$ctx=hash_init("sha256");foreach(DB::table($table)->where("id","!=",$exclude)->orderBy("id")->cursor() as $row){hash_update($ctx,json_encode($row)."\n");}return hash_final($ctx);};
    $tables=["articles","authors","categories","prisoners","prisoner_cases","dashboard_links"];$before=[];foreach($tables as $table){$before[$table]=$digest($table);}
    if(!$disk->exists($asset["path"]) && !$disk->put($asset["path"],$bytes,"public")){throw new \RuntimeException("Image installation failed.");}
    if(hash("sha256",$disk->get($asset["path"]))!==$asset["sha256"]){throw new \RuntimeException("Installed image checksum failed.");}
    $record=Article::create($a);
    foreach($tables as $table){if($before[$table]!==$digest($table,$table==="articles"?$record->id:"")){throw new \RuntimeException("Unrelated rows changed in ".$table);}}
    echo "Preservation verified: all preexisting articles, authors, categories, prisoners, cases and dashboard links unchanged\n";
    echo "Published: ",$record->url,"\n";return 1;
});
if(!$dry && $result>0){Cache::forget(PrisonerApiController::cacheKey());}
echo ($dry?"Would publish: ":"Published: "),$result,"\n";
echo "B494-OK\n";
'
run "publish-watson-news" "B494-OK" "$ADD_CODE" || exit 1
echo "Batch 494 complete (dry run: ${NPPC_BATCH_DRY_RUN})."
