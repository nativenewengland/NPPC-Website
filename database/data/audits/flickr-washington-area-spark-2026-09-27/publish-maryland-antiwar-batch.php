<?php

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
function must6(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }

$rows = [
 ['Younos Mokhtarzada','Male','1972-04-01','month','Maryland',['Anti-War','Student Activism'],'Arson for allegedly setting a curtain fire at the University of Maryland ROTC armory','No sentence; prosecutors dropped the charge for lack of evidence.','No — charge dropped','was an Afghan student and antiwar activist at the University of Maryland. Police arrested him in April 1972 and accused him of setting a small fire during a protest at the campus ROTC armory. Fellow activists said the accusation was a frame-up, and prosecutors dropped the charge for lack of evidence.'],
 ['Gregory Dunkel','Male','1972-04-01','month','Maryland',['Anti-War','Student Activism'],'Inciting to arson during a University of Maryland antiwar protest','No sentence; prosecutors dropped the charge for lack of evidence.','No — charge dropped','was a former University of Maryland instructor and a prominent campus antiwar organizer. Police arrested him after an April 1972 protest and charged him with inciting an ROTC armory curtain fire. Prosecutors later dropped the charge for lack of evidence.'],
 ['Steve Moore','Male','1972-05-01','month','Maryland',['Anti-War','Student Activism'],'Molesting university property for allegedly lighting a trash can fire','No sentence; prosecutors dropped the charge.','No — charge dropped','was a University of Maryland student activist arrested during the spring 1972 antiwar protests and accused of lighting a trash can fire on campus. Prosecutors later dropped the charge.'],
 ['Marc Cullen','Male','1972-05-10','day','Maryland',['Anti-War','Student Activism'],'Assault-related charge arising from an encounter with campus police','One year in jail, suspended after the judge required a letter admitting guilt and expressing regret.','Yes — sentence suspended','was a University of Maryland student arrested during an antiwar rally on May 10, 1972. Campus police dragged and beat him after alleging that he body-blocked an officer. A first trial ended with a hung jury; a second jury convicted him and the judge imposed a one-year jail term, then suspended it after requiring Cullen to write a statement admitting guilt and expressing regret.'],
 ['Betsy Banes Bell','Female','1972-05-10','day','Maryland',['Anti-War','Student Activism'],'Charge arising from efforts to intervene in the arrest of Marc Cullen','No completed sentence; the trial judge set aside the conviction because of trial error.','Conviction set aside','was prosecuted after students tried to intervene in campus police officers’ violent arrest of Marc Cullen during a University of Maryland antiwar rally on May 10, 1972. Her first trial ended with a hung jury; after a second trial, the judge set aside a lesser-offense conviction because of trial error.'],
 ['Edward Stubbs','Male','1972-05-10','day','Maryland',['Anti-War','Student Activism'],'Charge arising from efforts to intervene in the arrest of Marc Cullen','Probation before judgment under a plea agreement.','Plea agreement — probation before judgment','was prosecuted after students tried to intervene in campus police officers’ violent arrest of Marc Cullen during a University of Maryland antiwar rally on May 10, 1972. Stubbs entered a plea agreement and received probation before judgment.'],
 ['William Treanor','Male','1969-10-20','day','District of Columbia',['Anti-Freeway','Community Organizing'],'Using a voice amplifier without a permit','The available account documents the arrest but does not establish a custodial sentence.',null,'was a leader of the campaign against the proposed Three Sisters Bridge and the freeway system that threatened to displace District of Columbia neighborhoods. Police arrested him on October 20, 1969 during a construction-site protest, charging him with using a voice amplifier without a permit.'],
];

$names=array_column($rows,0);$dupes=Prisoner::withoutGlobalScopes()->whereIn('name',$names)->get(['name']);
must6($dupes->isEmpty(),'Profiles already exist: '.$dupes->pluck('name')->join(', '));
$mode=$argv[1]??'check';must6(in_array($mode,['check','apply'],true),'Use check or apply.');
if($mode==='check'){echo json_encode(['ready'=>true,'new_profiles'=>count($rows),'new_cases'=>count($rows)],JSON_PRETTY_PRINT);exit;}
$backup=storage_path('app/backups/before-flickr-maryland-antiwar-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));
$created=DB::transaction(function()use($rows):array{$created=[];foreach($rows as[$name,$gender,$date,$precision,$state,$ideologies,$charges,$sentence,$convicted,$bio]){$parts=preg_split('/\s+/',$name);$p=Prisoner::create(['name'=>$name,'first_name'=>$parts[0],'last_name'=>end($parts),'gender'=>$gender,'era'=>substr($date,0,3).'0s','state'=>$state,'affiliation'=>[],'ideologies'=>$ideologies,'description'=>$name.' '.$bio,'in_custody'=>false,'released'=>true,'in_exile'=>false,'currently_in_exile'=>false,'awaiting_trial'=>false,'minor_case'=>true]);PrisonerCase::create(['prisoner_id'=>$p->id,'arrest_date'=>$date,'date_precision'=>['arrest_date'=>$precision],'charges'=>$charges,'sentence'=>$sentence,'convicted'=>$convicted]);$created[]=$name;}return$created;});
Cache::forget(PrisonerApiController::cacheKey());Cache::forget('museum:payload:v2');
must6(Prisoner::withoutGlobalScopes()->whereIn('name',$created)->count()===count($created),'Profile verification failed');foreach($created as$name)must6(Prisoner::withoutGlobalScopes()->where('name',$name)->withCount('cases')->sole()->cases_count===1,"Case verification failed: $name");
echo json_encode(['backup'=>$backup,'created'=>$created],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
