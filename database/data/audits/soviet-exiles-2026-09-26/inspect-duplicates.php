<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$names = [
    'Craig W. Anderson', 'John Barilla', 'Richard D. Bailey', 'Michael A. Lindner',
    'Maurice Hyman Halperin', 'Wade E. Roberts', 'Arnold Lockshin', 'Morris Cohen',
    'Lona Cohen', 'Joel Barr', 'Alfred Sarant', 'Edward Lee Howard',
    'Glenn Michael Souther', 'William Hamilton Martin', 'Bernon F. Mitchell',
];

echo App\Models\Prisoner::withoutGlobalScopes()
    ->whereIn('name', $names)
    ->with('cases')
    ->get()
    ->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
