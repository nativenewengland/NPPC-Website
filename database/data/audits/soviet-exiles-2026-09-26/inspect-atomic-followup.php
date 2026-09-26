<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = [
    'Oscar Seborer', 'Stuart Seborer', 'Miriam Zeitlin Seborer', 'Miriam Seborer', 'George Koval',
    'Anatoly P. Kotloby', 'Anatole P. Kotloby', 'Orest Stephen Makar', 'James McMillin',
    'John Discoe Smith', 'William Donal Adkins', 'William Turner', 'J. W. Wright', 'J.W. Wright',
    'Annabelle Bucar', 'Annabel Bucar',
];
$rows = Prisoner::withoutGlobalScopes()
    ->where(function ($query) use ($names) {
        $query->whereIn('name', $names);
        foreach ($names as $name) {
            $query->orWhere('aka', 'like', '%'.$name.'%');
        }
    })
    ->with('cases')
    ->get()
    ->map(fn ($profile) => [
        'id' => $profile->id,
        'name' => $profile->name,
        'aka' => $profile->aka,
        'slug' => $profile->slug,
        'under_review' => $profile->under_review,
        'cases' => $profile->cases->count(),
    ]);

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
