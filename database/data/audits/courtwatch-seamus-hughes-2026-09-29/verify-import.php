<?php

use App\Models\Prisoner;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$profiles = json_decode((string) file_get_contents(__DIR__.'/profiles.json'), true, 512, JSON_THROW_ON_ERROR);
$results = [];

foreach ($profiles as $expected) {
    $prisoner = Prisoner::withUnderReview()->with('cases')->where('slug', $expected['slug'])->first();
    $request = curl_init('https://politicalprisonercoalition.org/prisoner/'.$expected['slug']);
    curl_setopt_array($request, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($request);
    $httpStatus = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    curl_close($request);
    $results[] = [
        'slug' => $expected['slug'],
        'found' => (bool) $prisoner,
        'case_count' => $prisoner?->cases->count() ?? 0,
        'counter_days' => $prisoner?->cases->sum(fn ($case) => $case->imprisoned_for_days) ?? null,
        'in_custody' => $prisoner?->in_custody,
        'released' => $prisoner?->released,
        'awaiting_trial' => $prisoner?->awaiting_trial,
        'photo' => $prisoner?->photo,
        'http_status' => $httpStatus,
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
