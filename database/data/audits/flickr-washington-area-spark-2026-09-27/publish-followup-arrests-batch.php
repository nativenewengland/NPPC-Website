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
function must3(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }

$rows = [
 ['Walter Fauntroy','Male','1984-11-21','District of Columbia',['Free South Africa Movement'],['Anti-Apartheid'],'Illegally congregating within 500 feet of an embassy','was arrested with Randall Robinson and Mary Frances Berry on November 21, 1984 after they refused to leave the South African Embassy. Their sit-in launched the Free South Africa Movement’s daily civil-disobedience campaign against apartheid.'],
 ['Yolanda King','Female','1984-11-29','District of Columbia',['Free South Africa Movement'],['Anti-Apartheid'],'Resisting police orders during an anti-apartheid demonstration','was jailed on November 29, 1984 after she joined hands with other activists in the South African Embassy driveway and refused police orders to leave. She pleaded not guilty the next day; prosecutors dismissed the charge for lack of prosecutive merit.'],
 ['Amy Carter','Female','1985-04-08','District of Columbia',['Free South Africa Movement'],['Anti-Apartheid'],'Demonstrating within 500 feet of an embassy','was seventeen when police arrested her on April 8, 1985 after she entered the South African Embassy grounds with two other protesters and sang a civil-rights anthem against apartheid.'],
 ['Coleman Young','Male','1985-01-07','District of Columbia',['Free South Africa Movement'],['Anti-Apartheid','Civil Rights'],'Demonstrating within 500 feet of an embassy','was Detroit’s mayor and a veteran labor and civil-rights organizer when police arrested him at the South African Embassy on January 7, 1985 during the Free South Africa Movement’s campaign against apartheid.'],
 ['Sandra Perrin','Female','1985-02-15','District of Columbia',['Amalgamated Transit Union Local 689','Free South Africa Movement'],['Anti-Apartheid','Labor'],'Demonstrating within 500 feet of an embassy','was an Amalgamated Transit Union Local 689 shop steward arrested at the South African Embassy on February 15, 1985 during a union delegation’s protest against apartheid.'],
 ['Theodore H. Parrish','Male','1985-02-15','District of Columbia',['Amalgamated Transit Union Local 689','Free South Africa Movement'],['Anti-Apartheid','Labor'],'Demonstrating within 500 feet of an embassy','was an Amalgamated Transit Union Local 689 officer arrested at the South African Embassy on February 15, 1985 during a union delegation’s protest against apartheid.'],
 ['Joseph Brinton “Brint” Dillingham','Male','1969-03-21','Maryland',['Washington Free Press','Compeers'],['Free Speech','Anti-War'],'Distributing allegedly obscene material','was a Washington Free Press distributor and director of the Bethesda youth center Freedom House. Police arrested him on March 21, 1969 for selling an issue that used an explicit political cartoon to criticize Judge James Pugh. A jury convicted him and imposed six months in jail, but the Maryland Court of Special Appeals reversed the conviction on July 15, 1970.'],
 ['Samuel Yudkin','Male','1961-10-26','Maryland',[],['Free Speech'],'Selling an allegedly obscene book','owned a Bethesda bookstore. Police arrested him after officers bought Henry Miller’s Tropic of Cancer there on October 26, 1961. A jury convicted Yudkin and imposed six months in jail; Maryland’s highest court reversed because the trial judge had excluded evidence about literary merit and community standards.'],
 ['Carroll Carrozza','Female','1966-07-23','Maryland',[],['Anti-Racism'],'Disorderly conduct after disrupting a Ku Klux Klan rally','was arrested in Mount Rainier, Maryland on July 23, 1966 after she and Carolyn Banks seized a Ku Klux Klan bullhorn, sang “We Shall Overcome,” and broke up a street-corner rally. Prosecutors dropped the charge when the Klansmen failed to appear for trial.'],
 ['Carolyn Banks','Female','1966-07-23','Maryland',[],['Anti-Racism'],'Disorderly conduct after disrupting a Ku Klux Klan rally','was arrested in Mount Rainier, Maryland on July 23, 1966 after she and Carroll Carrozza seized a Ku Klux Klan bullhorn, sang “We Shall Overcome,” and broke up a street-corner rally. Prosecutors dropped the charge when the Klansmen failed to appear for trial.'],
 ['Joan Hardy','Female','1932-03-26','District of Columbia',['Communist Party USA'],['Anti-Imperialism','Anti-Fascism'],'Parading without a permit and possible assault counts','was among thirty demonstrators arrested near the Japanese Embassy on March 26, 1932 while protesting Japan’s invasion of Manchuria. Police attacked the sidewalk demonstration with clubs and blackjacks and knocked Hardy unconscious before taking the protesters to the Third Precinct station.'],
 ['William “Preacherman” Fesperman','Male','1970-02-22','New York',['Patriot Party'],['Socialism','Anti-Racism'],'Weapons and related charges','was national chair of the socialist, anti-racist Patriot Party. New York police arrested him and the organization’s entire leadership on February 22, 1970 after legally registered firearms were carried into a building. All charges were later dropped.'],
 ['Judy Erickson','Female','1970-02-22','New York',['Patriot Party'],['Socialism','Anti-Racism'],'Weapons and related charges','was a Patriot Party leader arrested with the organization’s national leadership in New York on February 22, 1970 after legally registered firearms were carried into a building. All charges were later dropped.'],
 ['Nancy Willis','Female','1970-02-22','New York',['Patriot Party'],['Socialism','Anti-Racism'],'Weapons and related charges','was a Patriot Party leader arrested with the organization’s national leadership in New York on February 22, 1970 after legally registered firearms were carried into a building. All charges were later dropped.'],
];

$names = array_column($rows, 0);
$dupes = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->get(['name']);
must3($dupes->isEmpty(), 'Profiles already exist: '.$dupes->pluck('name')->join(', '));
$mode = $argv[1] ?? 'check'; must3(in_array($mode, ['check','apply'], true), 'Use check or apply.');
if ($mode === 'check') { echo json_encode(['ready'=>true,'new_profiles'=>count($rows),'new_cases'=>count($rows)], JSON_PRETTY_PRINT); exit; }
$backup = storage_path('app/backups/before-flickr-followup-arrests-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));
$created = DB::transaction(function () use ($rows): array {
 $created=[];
 foreach($rows as [$name,$gender,$date,$state,$affiliation,$ideologies,$charges,$bio]){
  $parts=preg_split('/\s+/',str_replace(['“','”'],'',$name));
  $p=Prisoner::create(['name'=>$name,'first_name'=>$parts[0],'last_name'=>end($parts),'gender'=>$gender,'era'=>substr($date,0,3).'0s','state'=>$state,'affiliation'=>$affiliation,'ideologies'=>$ideologies,'description'=>$name.' '.$bio,'in_custody'=>false,'released'=>true,'in_exile'=>false,'currently_in_exile'=>false,'awaiting_trial'=>false,'minor_case'=>true]);
  $case=['prisoner_id'=>$p->id,'arrest_date'=>$date,'date_precision'=>['arrest_date'=>'day'],'charges'=>$charges,'sentence'=>'The documented arrest was brief; no completed custodial sentence is established.'];
  if(str_contains($name,'Dillingham')){$case['convicted']='Yes — later reversed on appeal';$case['sentence']='Six months in jail; conviction reversed before the sentence was served.';}
  if($name==='Samuel Yudkin'){$case['convicted']='Yes — reversed on appeal';$case['sentence']='Six months in jail; conviction reversed and remanded because relevant defense evidence had been excluded.';}
  if(in_array($name,['Carroll Carrozza','Carolyn Banks','William “Preacherman” Fesperman','Judy Erickson','Nancy Willis'],true))$case['convicted']='No — charges dropped';
  if($name==='Yolanda King')$case['convicted']='No — charge dismissed';
  PrisonerCase::create($case);$created[]=$name;
 }
 return $created;
});
Cache::forget(PrisonerApiController::cacheKey());Cache::forget('museum:payload:v2');
must3(Prisoner::withoutGlobalScopes()->whereIn('name',$created)->count()===count($created),'Profile verification failed');
foreach($created as $name)must3(Prisoner::withoutGlobalScopes()->where('name',$name)->withCount('cases')->sole()->cases_count===1,"Case verification failed: $name");
echo json_encode(['backup'=>$backup,'created'=>$created],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
