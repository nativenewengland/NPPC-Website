#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
php <<'PHP'
<?php
require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$p=json_decode(file_get_contents("database/data/fixes/batch1192.json"),true,512,JSON_THROW_ON_ERROR);
DB::statement("PRAGMA busy_timeout=15000");
DB::transaction(function()use($p){
DB::table("prisoner_cases")->whereRaw("0=1")->update(["updated_at"=>null]);
$e=$p["expected"];$id=$e["prisoner"]["id"];$new=$p["new_case"];
if((array)DB::table("prisoners")->where("id",$id)->first()!=$e["prisoner"]){throw new RuntimeException("Profile changed");}
foreach($e["cases"] as $c){if((array)DB::table("prisoner_cases")->where("id",$c["id"])->first()!=$c){throw new RuntimeException("Existing case changed");}}
$found=DB::table("prisoner_cases")->where("id",$new["id"])->first();
if($found){foreach($new as $k=>$v){if($found->$k!==$v){throw new RuntimeException("Replay mismatch");}}echo "Already added\n";return;}
if(DB::table("prisoner_cases")->where("prisoner_id",$id)->count()!==count($e["cases"])){throw new RuntimeException("Unexpected additional case");}
$new["created_at"]=$new["updated_at"]=now()->format("Y-m-d H:i:s");DB::table("prisoner_cases")->insert($new);
echo "Added separate Andrew Augustyniak-Duncan federal civil-disorder case\n";
});
Illuminate\Support\Facades\Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());echo "B1192-OK\n";
PHP
