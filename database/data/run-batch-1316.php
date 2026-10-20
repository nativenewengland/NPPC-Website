<?php
require '/var/www/NPPC-Website/vendor/autoload.php';$app=require '/var/www/NPPC-Website/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Cache;
$d=json_decode(file_get_contents('/tmp/batch1316.json'),true,512,JSON_THROW_ON_ERROR);
DB::transaction(function()use($d){foreach($d['rows'] as $item){$e=$item['expected'];$r=(array)DB::table($item['table'])->where('id',$e['id'])->first();$after=array_merge($e,$item['updates']);if($r===$after){echo "Already applied\n";continue;}if($r!==$e)throw new RuntimeException('Row changed');DB::table($item['table'])->where('id',$e['id'])->update($item['updates']);echo "Ramos correction applied\n";}});
Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());echo "B1316-OK\n";
