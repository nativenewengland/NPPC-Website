<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = ['Edwin P. Wilson', 'Tsutomu Shirosaki'];
$rows = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get()->map(fn ($profile) => [
    'name' => $profile->name,
    'slug' => $profile->slug,
    'in_exile' => (bool) $profile->in_exile,
    'currently_in_exile' => (bool) $profile->currently_in_exile,
    'under_review' => (bool) $profile->under_review,
    'has_sources' => str_contains((string) $profile->body, '<h2>Sources</h2>'),
    'cases' => $profile->cases->map(fn ($case) => [
        'id' => $case->id,
        'arrest_date' => $case->partialDateIso('arrest_date'),
        'sentenced_date' => $case->partialDateIso('sentenced_date'),
        'release_date' => $case->partialDateIso('release_date'),
        'in_exile_since' => $case->partialDateIso('in_exile_since'),
        'end_of_exile' => $case->partialDateIso('end_of_exile'),
        'in_exile_for_days' => $case->in_exile_for_days,
    ])->values(),
])->sortBy('name')->values();

$wilson = $rows->firstWhere('name', 'Edwin P. Wilson');
$shirosaki = $rows->firstWhere('name', 'Tsutomu Shirosaki');
$ok = $wilson['in_exile']
    && ! $wilson['currently_in_exile']
    && ! $shirosaki['in_exile']
    && ! $shirosaki['currently_in_exile']
    && $shirosaki['cases']->first()['in_exile_since'] === null
    && $shirosaki['cases']->first()['end_of_exile'] === null
    && $shirosaki['cases']->first()['sentenced_date'] === '1998-02-20';
if (! $ok) {
    throw new RuntimeException('Exile-scope verification failed');
}

echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
