<?php
chdir('/var/www/NPPC-Website'); require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Prisoner;
$terms=['Rosemary Leary','Rosemary Woodruff Leary','Rosemary Woodruff','Bill Stephens','Bill Stevens','Dave Jacobs','David Jacobs','Ian Black','Bill Perry','Elaine Mokhtefi','Elaine Klein'];
$out=[]; foreach($terms as $term){$x=mb_strtolower($term); $out[$term]=Prisoner::withoutGlobalScopes()->whereRaw('lower(name)=?',[$x])->orWhereRaw('lower(aka)=?',[$x])->orWhereRaw('lower(aka) like ?',[$x.';%'])->orWhereRaw('lower(aka) like ?', ['%; '.$x.';%'])->orWhereRaw('lower(aka) like ?', ['%; '.$x])->get(['id','name','aka','slug','in_exile','currently_in_exile','description'])->toArray();} echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
