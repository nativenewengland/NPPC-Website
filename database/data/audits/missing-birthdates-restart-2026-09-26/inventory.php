<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('prisoners as p')
    ->leftJoin('prisoner_cases as c', 'c.prisoner_id', '=', 'p.id')
    ->whereNull('p.birthdate')
    ->select([
        'p.id', 'p.name', 'p.slug', 'p.aka', 'p.birthdate', 'p.death_date',
        'p.date_precision', 'p.photo', 'p.description', 'p.body', 'p.era',
        'p.in_custody', 'p.released', 'c.id as case_id', 'c.charges',
        'c.arrest_date', 'c.incarceration_date', 'c.release_date', 'c.sentence',
    ])
    ->orderBy('p.era')->orderBy('p.name')->orderBy('c.arrest_date')->get();

$profiles = [];
foreach ($rows as $row) {
    if (! isset($profiles[$row->id])) {
        $profiles[$row->id] = [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
            'aka' => $row->aka,
            'death_date' => $row->death_date,
            'date_precision' => $row->date_precision,
            'photo' => $row->photo,
            'description' => $row->description,
            'body' => $row->body,
            'era' => $row->era,
            'in_custody' => $row->in_custody,
            'released' => $row->released,
            'cases' => [],
        ];
    }
    if ($row->case_id) {
        $profiles[$row->id]['cases'][] = [
            'id' => $row->case_id,
            'charges' => $row->charges,
            'arrest_date' => $row->arrest_date,
            'incarceration_date' => $row->incarceration_date,
            'release_date' => $row->release_date,
            'sentence' => $row->sentence,
        ];
    }
}

$byEra = [];
foreach ($profiles as $profile) {
    $era = $profile['era'] ?: 'unspecified';
    $byEra[$era] = ($byEra[$era] ?? 0) + 1;
}
ksort($byEra);

echo json_encode([
    'generated_at' => now()->toIso8601String(),
    'total_missing_birthdate' => count($profiles),
    'counts_by_era' => $byEra,
    'profiles' => array_values($profiles),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
