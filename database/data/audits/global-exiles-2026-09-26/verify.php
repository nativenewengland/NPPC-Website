<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$names = [
    'Clarence Adams', 'Howard Gayle Adams', 'Albert Constant Belhomme', 'Otho Grayson Bell',
    'Richard Gordon', 'William Cowart', 'Rufus Douglas', 'John Roedel Dunn', 'Andrew Fortuna',
    'Lewis Wayne Griggs', 'Samuel David Hawkins', 'Arlie Pate', 'Scott Rush', 'Lowell Skinner',
    'LaRance Sullivan', 'Richard Tenneson', 'James Veneris', 'Harold Webb', 'William White',
    'Morris Wills', 'Aaron Wilson', 'Terry Marvell Whitmore', 'Larry Allen Abshier',
    'James Joseph Dresnok', 'Jerry Wayne Parrish', 'Charles Robert Jenkins',
];

$profiles = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get();
$rows = $profiles->map(fn ($profile) => [
    'name' => $profile->name,
    'slug' => $profile->slug,
    'in_exile' => $profile->in_exile,
    'currently_in_exile' => $profile->currently_in_exile,
    'under_review' => $profile->under_review,
    'cases' => $profile->cases->map(fn ($case) => [
        'in_exile_since' => optional($case->in_exile_since)->format('Y-m-d'),
        'end_of_exile' => optional($case->end_of_exile)->format('Y-m-d'),
        'date_precision' => $case->date_precision,
        'documented_imprisoned_for_days' => $case->documented_imprisoned_for_days,
    ])->values(),
])->sortBy('name')->values();

$updated = Prisoner::withoutGlobalScopes()
    ->whereIn('name', ['Don Cox', 'Kathleen Cleaver', 'Eldridge Cleaver'])
    ->with('cases')
    ->get()
    ->map(fn ($profile) => [
        'name' => $profile->name,
        'has_sources' => str_contains((string) $profile->body, '<h2>Sources</h2>'),
        'case_dates' => $profile->cases->map(fn ($case) => [
            'in_exile_since' => optional($case->in_exile_since)->format('Y-m-d'),
            'end_of_exile' => optional($case->end_of_exile)->format('Y-m-d'),
            'date_precision' => $case->date_precision,
        ])->values(),
    ])->sortBy('name')->values();

echo json_encode([
    'new_profile_count' => $profiles->count(),
    'new_profiles' => $rows,
    'updated_profiles' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
