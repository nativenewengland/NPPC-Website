<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = ['Don Cox', 'Eldridge Cleaver', 'Kathleen Cleaver', 'Catherine Kerkow', 'Willie Roger Holder'];
$rows = Prisoner::withoutGlobalScopes()
    ->whereIn('name', $names)
    ->with('cases')
    ->get()
    ->map(fn ($profile) => $profile->toArray());

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
