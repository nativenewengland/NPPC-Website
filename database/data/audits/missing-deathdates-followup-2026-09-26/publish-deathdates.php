<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$entries = json_decode(file_get_contents('/tmp/missing-deathdate-updates.json'), true, 512, JSON_THROW_ON_ERROR);

foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
    if ($prisoner->name !== $entry['name'] || $prisoner->slug !== $entry['slug']) {
        throw new RuntimeException('Identity mismatch: '.$entry['name']);
    }
    if ($prisoner->getRawOriginal('death_date') !== null) {
        throw new RuntimeException('Concurrent death-date change: '.$entry['name']);
    }
}

$backup = storage_path('app/backups/before-missing-deathdates-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$updated = DB::transaction(function () use ($entries) {
    $results = [];
    foreach ($entries as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
        $before = $prisoner->getAttributes();
        $precision = $prisoner->date_precision ?? [];
        $prisoner->death_date = $entry['death_date'];
        $precision['death_date'] = $entry['death_precision'];
        $prisoner->date_precision = $precision;
        if (! str_contains((string) $prisoner->body, $entry['source_url'])) {
            $prisoner->body = rtrim((string) $prisoner->body).$entry['credit'];
        }
        $prisoner->save();
        $prisoner->refresh();

        foreach ($before as $field => $value) {
            if (! in_array($field, ['death_date', 'date_precision', 'age', 'body', 'updated_at'], true)
                && $prisoner->getRawOriginal($field) !== $value) {
                throw new RuntimeException('Unexpected change: '.$entry['name'].' '.$field);
            }
        }

        $results[] = [
            'id' => $prisoner->id,
            'name' => $prisoner->name,
            'slug' => $prisoner->slug,
            'death_date' => substr((string) $prisoner->getRawOriginal('death_date'), 0, 10),
            'death_precision' => ($prisoner->date_precision ?? [])['death_date'] ?? null,
        ];
    }
    return $results;
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['backup' => $backup, 'updated_count' => count($updated), 'updated' => $updated], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
