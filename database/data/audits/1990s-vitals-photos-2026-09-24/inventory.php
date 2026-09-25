<?php

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('prisoners as p')
    ->leftJoin('prisoner_cases as c', 'c.prisoner_id', '=', 'p.id')
    ->where(function ($query) {
        $query->where('p.era', '1990s')
            ->orWhereBetween('c.arrest_date', ['1990-01-01', '1999-12-31'])
            ->orWhereBetween('c.incarceration_date', ['1990-01-01', '1999-12-31']);
    })
    ->where(function ($query) {
        $query->whereNull('p.birthdate')
            ->orWhereNull('p.death_date')
            ->orWhereNull('p.photo');
    })
    ->select([
        'p.id', 'p.name', 'p.slug', 'p.aka', 'p.birthdate', 'p.death_date',
        'p.date_precision', 'p.photo', 'p.description', 'p.body', 'p.era',
        'p.in_custody', 'p.released', 'c.id as case_id', 'c.charges',
        'c.arrest_date', 'c.incarceration_date', 'c.release_date', 'c.sentence',
    ])
    ->orderBy('p.name')
    ->orderBy('c.arrest_date')
    ->get();

$profiles = [];
foreach ($rows as $row) {
    $id = $row->id;
    if (! isset($profiles[$id])) {
        $profiles[$id] = [
            'id' => $id,
            'name' => $row->name,
            'slug' => $row->slug,
            'aka' => $row->aka,
            'birthdate' => $row->birthdate,
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
        $profiles[$id]['cases'][] = [
            'id' => $row->case_id,
            'charges' => $row->charges,
            'arrest_date' => $row->arrest_date,
            'incarceration_date' => $row->incarceration_date,
            'release_date' => $row->release_date,
            'sentence' => $row->sentence,
        ];
    }
}

echo json_encode(array_values($profiles), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
