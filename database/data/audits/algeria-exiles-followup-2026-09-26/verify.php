<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = [
    'Byron Vaughn Booth', 'Clinton Robert Smith Jr.', 'Rosemary Woodruff Leary',
    'Barbara Easley-Cox', 'Michael Tabor', 'Larry Mack', 'Sekou Odinga',
    'Dhoruba bin Wahad', 'Connie Matthews', 'Timothy Leary',
];

$rows = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get()->map(fn ($p) => [
    'name' => $p->name,
    'slug' => $p->slug,
    'birthdate' => $p->birthdate?->format('Y-m-d'),
    'death_date' => $p->death_date?->format('Y-m-d'),
    'date_precision' => $p->date_precision,
    'in_exile' => $p->in_exile,
    'currently_in_exile' => $p->currently_in_exile,
    'under_review' => $p->under_review,
    'cases' => $p->cases->map(fn ($c) => [
        'id' => $c->id,
        'charges' => $c->charges,
        'in_exile_since' => $c->in_exile_since?->format('Y-m-d'),
        'end_of_exile' => $c->end_of_exile?->format('Y-m-d'),
        'in_exile_for_days' => $c->in_exile_for_days,
        'date_precision' => $c->date_precision,
    ])->values(),
])->sortBy('name')->values();

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
