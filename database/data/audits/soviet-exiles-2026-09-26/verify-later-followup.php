<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = [
    'Bruce Frederick Davis', 'Joseph Dutkanicz', 'Vladimir Sloboda', 'Victor Norris Hamilton',
    'Harold M. Koch', 'Theodore Branch', 'Cheryl Branch', 'Oscar Seborer', 'Stuart Seborer',
    'Miriam Zeitlin Seborer', 'George Koval', 'Anatoly P. Kotloby', 'James McMillin',
    'John Discoe Smith', 'Annabelle Bucar', 'Orest Stephen Makar', 'Alexander Kazem-Bek',
];

$profiles = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get()->keyBy('name');
$results = [];
foreach ($names as $name) {
    $profile = $profiles->get($name);
    $results[] = $profile ? [
        'name' => $profile->name,
        'slug' => $profile->slug,
        'public' => ! $profile->under_review,
        'in_exile' => (bool) $profile->in_exile,
        'currently_in_exile' => (bool) $profile->currently_in_exile,
        'cases' => $profile->cases->count(),
        'has_exile_start' => $profile->cases->contains(fn ($case) => (bool) $case->in_exile_since),
    ] : ['name' => $name, 'missing' => true];
}

$ok = count($results) === count($names)
    && collect($results)->every(fn ($row) => empty($row['missing']) && $row['public'] && $row['in_exile'] && $row['cases'] >= 1 && $row['has_exile_start']);

echo json_encode(['verified' => $ok, 'count' => count($results), 'profiles' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($ok ? 0 : 1);
