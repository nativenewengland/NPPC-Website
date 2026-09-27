<?php

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$entries = json_decode(file_get_contents(__DIR__.'/photo-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
if (count($entries) !== 40) {
    throw new RuntimeException('Expected forty verified portrait entries.');
}

foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->where('name', $entry['name'])->sole();
    if ($prisoner->photo !== null) {
        throw new RuntimeException('Profile already has a photo: '.$entry['name']);
    }
    $source = __DIR__.'/photos/'.$entry['filename'];
    if (! is_file($source) || hash_file('sha256', $source) !== $entry['sha256']) {
        throw new RuntimeException('Crop validation failed: '.$entry['name']);
    }
    if (Storage::disk('public')->exists($entry['photo'])) {
        throw new RuntimeException('Destination already exists: '.$entry['photo']);
    }
}

$mode = $argv[1] ?? 'check';
if (! in_array($mode, ['check', 'apply'], true)) {
    throw new RuntimeException('Use check or apply.');
}
if ($mode === 'check') {
    echo json_encode(['ready' => true, 'photos' => count($entries)], JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}

$backup = storage_path('app/backups/before-flickr-profile-photos-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$updated = DB::transaction(function () use ($entries) {
    $results = [];
    foreach ($entries as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->where('name', $entry['name'])->sole();
        $before = $prisoner->getAttributes();
        $source = __DIR__.'/photos/'.$entry['filename'];
        if (! Storage::disk('public')->put($entry['photo'], file_get_contents($source))) {
            throw new RuntimeException('Photo upload failed: '.$entry['name']);
        }

        $identification = in_array($entry['name'], ['Hugo Gellert', 'Livia Gellert'], true)
            ? ' The archive caption describes this identification as tentative.'
            : '';
        $credit = '<h2>Photo credit</h2><p><a href="'.htmlspecialchars($entry['source_url'], ENT_QUOTES).'">'
            .htmlspecialchars($entry['source_title'], ENT_QUOTES)
            .'</a>, Washington Area Spark Flickr archive. Portrait crop from the documentary photograph; used with permission.'
            .$identification.'</p>';

        $prisoner->photo = $entry['photo'];
        $prisoner->body = ($prisoner->body ?? '').$credit;
        $prisoner->saveQuietly();
        $prisoner->refresh();
        foreach ($before as $key => $value) {
            if (! in_array($key, ['photo', 'body', 'updated_at'], true) && $prisoner->getRawOriginal($key) !== $value) {
                throw new RuntimeException('Unexpected field changed for '.$entry['name'].': '.$key);
            }
        }
        $results[] = ['name' => $prisoner->name, 'slug' => $prisoner->slug, 'photo' => $prisoner->photo];
    }
    return $results;
});

Cache::forget(PrisonerApiController::cacheKey());
Cache::forget('museum:payload:v2');
echo json_encode(['backup' => $backup, 'updated' => $updated], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
