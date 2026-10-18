#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
php <<'PHP'
<?php
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$p=json_decode(file_get_contents("database/data/fixes/batch1172.json"),true,512,JSON_THROW_ON_ERROR);
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p){
foreach($p["profiles"] as $e){
$id=$e["prisoner"]["id"];
$raw=DB::table("prisoners")->where("id",$id)->first();
$cases=DB::table("prisoner_cases")->where("prisoner_id",$id)->get()->map(fn($v)=>(array)$v)->all();
if(!$raw){if(count($cases)){throw new RuntimeException("Orphan case detected");}echo "Already removed: ",$e["prisoner"]["name"],"\n";continue;}
if((array)$raw!=$e["prisoner"]){throw new RuntimeException("Profile changed since snapshot");}
$want=$e["cases"];usort($cases,fn($a,$b)=>strcmp($a["id"],$b["id"]));usort($want,fn($a,$b)=>strcmp($a["id"],$b["id"]));
if($cases!=$want){throw new RuntimeException("Cases changed since snapshot");}
DB::table("prisoner_cases")->where("prisoner_id",$id)->delete();
DB::table("prisoners")->where("id",$id)->delete();
if(DB::table("prisoners")->where("id",$id)->exists()||DB::table("prisoner_cases")->where("prisoner_id",$id)->exists()){throw new RuntimeException("Removal verification failed");}
echo "Removed: ",$e["prisoner"]["name"],"; cases: ",count($cases),"\n";
}
});
Illuminate\Support\Facades\Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo "B1172-OK\n";
PHP
