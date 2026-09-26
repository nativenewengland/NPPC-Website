<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$updatesPath = $argv[1] ?? '/tmp/missing-birthdate-updates.json';
$entries = json_decode(file_get_contents($updatesPath), true, 512, JSON_THROW_ON_ERROR);
$before = [];

foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
    if ($prisoner->name !== $entry['name'] || $prisoner->slug !== $entry['slug']) {
        throw new RuntimeException('Identity mismatch: '.$entry['name']);
    }
    if ($prisoner->getRawOriginal('birthdate') !== null) {
        throw new RuntimeException('Concurrent birth-date change: '.$entry['name']);
    }
    $before[$entry['id']] = $prisoner->getAttributes();
}

$backup = storage_path('app/backups/before-missing-birthdates-restart-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$updated = DB::transaction(function () use ($entries, $before) {
    $results = [];
    foreach ($entries as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
        $precision = $prisoner->date_precision ?? [];
        $precision['birthdate'] = $entry['birth_precision'];
        $credit = '<hr><p><strong>Birth '.($entry['birth_precision'] === 'day' ? 'date' : 'year').' source:</strong> '
            .'<a href="'.htmlspecialchars($entry['source_url'], ENT_QUOTES).'" target="_blank" rel="noopener noreferrer">'
            .htmlspecialchars($entry['source_label']).'</a>.</p>';

        $prisoner->birthdate = $entry['birthdate'];
        $prisoner->date_precision = $precision;
        $prisoner->body = ($prisoner->body ?? '').$credit;
        $prisoner->save();
        $prisoner->refresh();

        foreach ($before[$entry['id']] as $field => $value) {
            if (! in_array($field, ['birthdate', 'date_precision', 'age', 'body', 'updated_at'], true)
                && $prisoner->getRawOriginal($field) !== $value) {
                throw new RuntimeException('Unexpected change: '.$entry['name'].' '.$field);
            }
        }
        if (substr((string) $prisoner->getRawOriginal('birthdate'), 0, 10) !== $entry['birthdate']) {
            throw new RuntimeException('Birth-date verification failed: '.$entry['name']);
        }
        $results[] = [
            'id' => $prisoner->id,
            'name' => $prisoner->name,
            'slug' => $prisoner->slug,
            'birthdate' => $prisoner->getRawOriginal('birthdate'),
            'date_precision' => $prisoner->getRawOriginal('date_precision'),
        ];
    }
    return $results;
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['backup' => $backup, 'updated' => $updated], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
