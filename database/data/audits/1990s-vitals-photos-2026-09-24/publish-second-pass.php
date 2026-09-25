<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$entries = [
    [
        'id' => '142569ec-398a-4c7a-9c05-156736ac0c32',
        'name' => 'Dan Sage',
        'expect' => ['birthdate' => null, 'death_date' => null, 'photo' => null],
        'dates' => ['death_date' => ['2014-01-12', 'day']],
        'photo' => [
            'file' => 'daniel-sage.jpg',
            'path' => 'prisoners/1990s-research/daniel-sage.jpg',
            'sha256' => '257c18ca42b205b919283b72ea78936c4721c9bccfcf80eec7fb7f50aa4e76ef',
        ],
        'source' => '<h2>Photo and death-date source</h2><p><a href="https://obits.syracuse.com/us/obituaries/syracuse/name/daniel-sage-obituary?id=23608253">Syracuse Post-Standard obituary for Daniel Sage</a>. The obituary identifies the Syracuse educator and prisoner of conscience, reports his death on January 12, 2014, and supplies the portrait shown here. The white border surrounding the supplied portrait was removed; no AI editing was used.</p>',
    ],
    [
        'id' => 'fe03f947-d1ec-4d8c-a8b9-62a85af66ca8',
        'name' => 'Clarence Davis',
        'expect' => ['birthdate' => null, 'death_date' => null, 'photo' => null],
        'dates' => ['death_date' => ['1994-02-08', 'day']],
        'source' => '<h2>Death-date source</h2><p><a href="https://www.marxists.org/history/etol/newspape/atc/976.html">Against the Current, “Honoring Our Gulf War Resisters”</a>. The memorial by Davis’s friend Betsy Esch gives his death date as February 8, 1994.</p>',
    ],
];

foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
    if ($prisoner->name !== $entry['name']) {
        throw new RuntimeException('Identity mismatch: '.$entry['name']);
    }
    foreach ($entry['expect'] as $field => $expected) {
        if ($prisoner->getRawOriginal($field) !== $expected) {
            throw new RuntimeException("Concurrent change: {$entry['name']} {$field}");
        }
    }
    if (isset($entry['photo'])) {
        $source = __DIR__.'/'.$entry['photo']['file'];
        if (hash_file('sha256', $source) !== $entry['photo']['sha256']) {
            throw new RuntimeException('Image checksum mismatch: '.$entry['name']);
        }
        if (Storage::disk('public')->exists($entry['photo']['path'])) {
            throw new RuntimeException('Image destination already exists: '.$entry['name']);
        }
    }
}

$backup = storage_path('app/backups/before-1990s-vitals-photos-second-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$results = DB::transaction(function () use ($entries) {
    $results = [];
    foreach ($entries as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
        $before = $prisoner->getAttributes();
        $precision = $prisoner->date_precision ?? [];
        foreach ($entry['dates'] as $field => [$date, $datePrecision]) {
            $prisoner->{$field} = $date;
            $precision[$field] = $datePrecision;
        }
        $prisoner->date_precision = $precision;
        if (isset($entry['photo'])) {
            $source = __DIR__.'/'.$entry['photo']['file'];
            if (! Storage::disk('public')->put($entry['photo']['path'], file_get_contents($source))) {
                throw new RuntimeException('Image upload failed: '.$entry['name']);
            }
            $prisoner->photo = $entry['photo']['path'];
        }
        $prisoner->body = ($prisoner->body ?? '').$entry['source'];
        $prisoner->save();
        $prisoner->refresh();

        $allowed = array_merge(array_keys($entry['dates']), ['date_precision', 'age', 'photo', 'body', 'updated_at']);
        foreach ($before as $field => $value) {
            if (! in_array($field, $allowed, true) && $prisoner->getRawOriginal($field) !== $value) {
                throw new RuntimeException("Unexpected change: {$entry['name']} {$field}");
            }
        }
        $results[] = [
            'name' => $prisoner->name,
            'death_date' => $prisoner->getRawOriginal('death_date'),
            'date_precision' => $prisoner->getRawOriginal('date_precision'),
            'photo' => $prisoner->photo,
        ];
    }
    return $results;
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['backup' => $backup, 'updated' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
