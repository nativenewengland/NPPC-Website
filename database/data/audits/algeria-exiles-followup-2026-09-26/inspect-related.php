<?php
chdir('/var/www/NPPC-Website'); require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Prisoner;
foreach(['Charlotte O’Neal','Charlotte O\'Neal','Pete O’Neal','Pete O\'Neal','Don Cox'] as $n){$p=Prisoner::withoutGlobalScopes()->where('name',$n)->with('cases')->first(); if($p) echo json_encode(['name'=>$p->name,'slug'=>$p->slug,'in_exile'=>$p->in_exile,'current'=>$p->currently_in_exile,'description'=>$p->description,'cases'=>$p->cases->map(fn($c)=>$c->only(['charges','in_exile_since','end_of_exile','date_precision']))],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;}
