<?php

use App\Models\Prisoner;
use Illuminate\Contracts\Console\Kernel;

$root = getcwd();
require $root.'/vendor/autoload.php';
$app = require_once $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$slugs = [
    'bill-haywood',
    'george-andreytchine',
    'vladimir-lossieff',
    'j-h-beyer',
    'herbert-mccutcheon',
    'grover-h-perry',
    'charles-rothfiser',
    'leo-laukki',
    'fred-jaakkola',
    'fred-beal',
    'clarence-miller',
    'george-carter',
    'joseph-harrison',
    'w-m-mcginnis',
    'louis-mclaughlin',
    'k-y-hendricks',
    'eugene-dennis',
];

$profiles = Prisoner::query()
    ->whereIn('slug', $slugs)
    ->with(['cases' => fn ($query) => $query->orderBy('id')])
    ->get()
    ->sortBy(fn (Prisoner $profile) => array_search($profile->slug, $slugs, true))
    ->map(function (Prisoner $profile) {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'aka' => $profile->aka,
            'slug' => $profile->slug,
            'in_exile' => (bool) $profile->in_exile,
            'currently_in_exile' => (bool) $profile->currently_in_exile,
            'description' => $profile->description,
            'cases' => $profile->cases->map(function ($case) {
                $raw = $case->getRawOriginal();
                return [
                    'id' => $case->id,
                    'charges' => $case->charges,
                    'arrest_date' => $raw['arrest_date'] ?? null,
                    'incarceration_date' => $raw['incarceration_date'] ?? null,
                    'release_date' => $raw['release_date'] ?? null,
                    'in_exile_since' => $raw['in_exile_since'] ?? null,
                    'end_of_exile' => $raw['end_of_exile'] ?? null,
                    'date_precision' => $raw['date_precision'] ?? null,
                    'sentence' => $case->sentence,
                ];
            })->values(),
        ];
    })->values();

echo json_encode([
    'exported_at' => now()->toIso8601String(),
    'requested_slugs' => $slugs,
    'found' => $profiles->count(),
    'profiles' => $profiles,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
