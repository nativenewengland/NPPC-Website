<?php
require '/var/www/NPPC-Website/vendor/autoload.php';$app=require '/var/www/NPPC-Website/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Cache;
$d=json_decode(file_get_contents('/tmp/batch1305.json'),true,512,JSON_THROW_ON_ERROR);
DB::transaction(function()use($d){foreach($d['before']['prisoners'] as $e){$r=DB::table('prisoners')->where('id',$e['id'])->first();if(!$r)throw new RuntimeException('Missing profile');$tags=json_decode($e['affiliation'],true);$tags=array_values(array_filter($tags,fn($x)=>$x!==$d['remove_affiliation']));$after=$tags?json_encode($tags,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null;if($r->affiliation===$after)continue;if($r->affiliation!==$e['affiliation'])throw new RuntimeException('Affiliations changed');DB::table('prisoners')->where('id',$e['id'])->update(['affiliation'=>$after]);}echo count($d['before']['prisoners'])." affiliation records verified\n";});
Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());echo "B1305-OK\n";
