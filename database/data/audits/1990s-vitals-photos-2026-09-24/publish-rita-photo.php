<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$id = 'b3ac4aab-be80-4133-935c-41d9b78ab4de';
$source = __DIR__.'/rita-steinhagen.jpg';
$destination = 'prisoners/1990s-research/rita-steinhagen.jpg';
$expectedHash = '5b38a1da1591fcbb1c96f8dcb5849c5f1e3c5a1b13994c88e475166e32e8afba';

$prisoner = Prisoner::withoutGlobalScopes()->findOrFail($id);
if ($prisoner->name !== 'Rita Steinhagen' || $prisoner->photo !== null) {
    throw new RuntimeException('Rita Steinhagen snapshot changed');
}
if (hash_file('sha256', $source) !== $expectedHash || Storage::disk('public')->exists($destination)) {
    throw new RuntimeException('Photo validation failed');
}

$backup = storage_path('app/backups/before-rita-steinhagen-photo-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

DB::transaction(function () use ($prisoner, $source, $destination) {
    if (! Storage::disk('public')->put($destination, file_get_contents($source))) {
        throw new RuntimeException('Photo upload failed');
    }
    $prisoner->photo = $destination;
    $prisoner->body = ($prisoner->body ?? '').'<h2>Photo credit</h2><p><a href="https://thewildreed.blogspot.com/2006/11/">Sister Rita Steinhagen at the November 1997 School of the Americas protest</a>. Photograph by Michael J. Bayly, who identifies Steinhagen in his account of taking the photograph; portrait crop from the documentary image, with no AI editing.</p>';
    $prisoner->save();
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['backup' => $backup, 'name' => $prisoner->name, 'photo' => $destination], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
