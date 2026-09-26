<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$terms = [
    'Edward Snowden', 'John Mark Dougan', 'Tara Reade', 'John Robles',
    'Leo Haer', 'Chantel Haer', 'Joseph Tater', 'Wilmer Puello-Mota',
    'Russell Bentley', 'John McIntyre',
];

$profiles = Prisoner::withoutGlobalScopes()
    ->where(function ($query) use ($terms) {
        foreach ($terms as $term) {
            $query->orWhere('name', 'like', '%'.$term.'%')->orWhere('aka', 'like', '%'.$term.'%');
        }
    })
    ->with('cases')
    ->get()
    ->map(fn ($profile) => [
        'id' => $profile->id,
        'name' => $profile->name,
        'aka' => $profile->aka,
        'slug' => $profile->slug,
        'in_exile' => (bool) $profile->in_exile,
        'currently_in_exile' => (bool) $profile->currently_in_exile,
        'under_review' => (bool) $profile->under_review,
        'birthdate' => (string) $profile->birthdate,
        'description' => $profile->description,
        'body' => $profile->body,
        'cases' => $profile->cases->map(fn ($case) => [
            'id' => $case->id,
            'charges' => $case->charges,
            'sentence' => $case->sentence,
            'convicted' => $case->convicted,
            'in_exile_since' => (string) $case->in_exile_since,
            'end_of_exile' => (string) $case->end_of_exile,
        ]),
    ]);

echo $profiles->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
