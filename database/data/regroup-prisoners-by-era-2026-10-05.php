<?php
// Run on the live Laravel host: php regroup-prisoners-by-era-2026-10-05.php [--apply]
// Stable era grouping; updates sort_order only and verifies all other fields.
ini_set('memory_limit', '1024M');
require '/var/www/NPPC-Website/vendor/autoload.php';
$app=ini_set('memory_limit', '1024M');
require '/var/www/NPPC-Website/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
$apply=in_array('--apply',$argv,true);
DB::transaction(function()use($apply){
 $rows=DB::table('prisoners')->orderBy('sort_order')->orderBy('id')->lockForUpdate()->get()->map(fn($r)=>(array)$r)->all();
 $ranked=[];foreach($rows as $i=>$r){if(!preg_match('/^\d{4}s$/',(string)$r['era']))throw new RuntimeException('Unrecognized era: '.$r['era']);$ranked[]=['row'=>$r,'i'=>$i,'era'=>(int)$r['era']];}
 usort($ranked,fn($a,$b)=>[-$a['era'],$a['i']]<=>[-$b['era'],$b['i']]);
 $changed=0;$transitions=[];$last=null;
 foreach($ranked as $i=>$entry){$r=$entry['row'];$order=($i+1)*10;if($last!==$r['era']){$transitions[]=['era'=>$r['era'],'first_order'=>$order];$last=$r['era'];}if((int)$r['sort_order']!==$order){$changed++;if($apply)DB::table('prisoners')->where('id',$r['id'])->update(['sort_order'=>$order]);}}
 if($apply){if(!file_exists('/tmp/prisoner-order-before-2026-10-05.json'))file_put_contents('/tmp/prisoner-order-before-2026-10-05.json',json_encode($rows,JSON_THROW_ON_ERROR));$after=DB::table('prisoners')->get()->keyBy('id');foreach($rows as $r){$a=(array)$after[$r['id']];unset($a['sort_order'],$r['sort_order']);if($a!=$r)throw new RuntimeException('Unrelated field changed');}}
 echo json_encode(['applied'=>$apply,'total'=>count($rows),'changed'=>$changed,'era_transitions'=>$transitions],JSON_PRETTY_PRINT).PHP_EOL;
});
if($apply){Cache::forget('api.prisoners.index.v3');Cache::forget('museum:payload:v2');app(App\Http\Controllers\Api\PrisonerApiController::class)->refreshCache();}