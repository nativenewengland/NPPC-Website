<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\Prisoner;
echo json_encode([
 'missing_death_dates'=>Prisoner::withoutGlobalScopes()->whereNull('death_date')->count(),
 'total_profiles'=>Prisoner::withoutGlobalScopes()->count(),
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
