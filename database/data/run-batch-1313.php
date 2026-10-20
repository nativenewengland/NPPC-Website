<?php
require '/var/www/NPPC-Website/vendor/autoload.php';$app=require '/var/www/NPPC-Website/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Cache;
$d=json_decode(file_get_contents('/tmp/batch1313.json'),true,512,JSON_THROW_ON_ERROR);
DB::transaction(function()use($d){$e=$d['expected'];$r=(array)DB::table('prisoners')->where('id',$e['id'])->first();$after=array_merge($e,$d['updates']);if($r===$after){echo "Already applied\n";return;}if($r!==$e)throw new RuntimeException('Case changed');DB::table('prisoners')->where('id',$e['id'])->update($d['updates']);echo "Cortez BOP number applied\n";});
Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());echo "B1313-OK\n";
