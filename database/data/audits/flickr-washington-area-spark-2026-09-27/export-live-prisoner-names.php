<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$rows = Prisoner::withoutGlobalScopes()
    ->orderBy('name')
    ->get(['id', 'name', 'aka', 'slug', 'description'])
    ->map(fn ($profile) => [
        'id' => $profile->id,
        'name' => $profile->name,
        'aka' => $profile->aka,
        'slug' => $profile->slug,
        'description' => $profile->description,
    ]);

echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
