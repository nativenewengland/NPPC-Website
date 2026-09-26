<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;

$entries = json_decode(file_get_contents('/tmp/missing-deathdate-updates.json'), true, 512, JSON_THROW_ON_ERROR);
$verified = [];
foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
    $actual = substr((string) $prisoner->getRawOriginal('death_date'), 0, 10);
    $precision = $prisoner->date_precision ?? [];
    if ($prisoner->name !== $entry['name'] || $prisoner->slug !== $entry['slug']) {
        throw new RuntimeException('Identity mismatch: '.$entry['name']);
    }
    if ($actual !== $entry['death_date'] || ($precision['death_date'] ?? null) !== $entry['death_precision']) {
        throw new RuntimeException('Value mismatch: '.$entry['name']);
    }
    if (! str_contains((string) $prisoner->body, $entry['source_url'])) {
        throw new RuntimeException('Source credit missing: '.$entry['name']);
    }
    $verified[] = [
        'id' => $prisoner->id,
        'name' => $prisoner->name,
        'slug' => $prisoner->slug,
        'death_date' => $actual,
        'death_precision' => $precision['death_date'],
    ];
}

echo json_encode([
    'verified_count' => count($verified),
    'remaining_missing_death_dates' => Prisoner::withoutGlobalScopes()->whereNull('death_date')->count(),
    'verified' => $verified,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
