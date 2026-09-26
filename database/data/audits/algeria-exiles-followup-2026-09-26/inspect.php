<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Prisoner;
$names = [
'Michael Tabor','Michael Aloysius Tabor','Cetewayo Tabor','Larry Mack',
'Byron Vaughn Booth','Byron Booth','Clinton Robert Smith Jr.','Clinton Robert Smith','Rahim Smith',
'Sekou Odinga','Nathaniel Burns','Dhoruba bin Wahad','Richard Moore',
'Victor Martinez','Timothy Leary','Barbara Easley-Cox','Barbara Easley Cox','Barbara Easley','Connie Matthews','Constance Matthews Tabor'
];
$out=[];
foreach($names as $name){
 $q=Prisoner::withoutGlobalScopes();
 $lower=mb_strtolower($name);
 $q->whereRaw('lower(name) = ?',[$lower])
   ->orWhereRaw('lower(aka) = ?',[$lower])
   ->orWhereRaw('lower(aka) like ?',[$lower.';%'])
   ->orWhereRaw('lower(aka) like ?', ['%; '.$lower.';%'])
   ->orWhereRaw('lower(aka) like ?', ['%; '.$lower]);
 $out[$name]=$q->with('cases')->get()->map(fn($p)=>[
  'id'=>$p->id,'name'=>$p->name,'aka'=>$p->aka,'slug'=>$p->slug,'birthdate'=>$p->birthdate?->format('Y-m-d'),'death_date'=>$p->death_date?->format('Y-m-d'),'date_precision'=>$p->date_precision,'in_exile'=>$p->in_exile,'currently_in_exile'=>$p->currently_in_exile,'under_review'=>$p->under_review,'description'=>$p->description,'body'=>$p->body,'cases'=>$p->cases->map(fn($c)=>$c->only(['id','charges','sentence','in_exile_since','end_of_exile','date_precision']))
 ])->toArray();
}
echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
