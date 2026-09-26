<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
$updates = [
    'Vladimir Lossieff' => 'Vladimir Lossieff was a New York editor and Industrial Workers of the World member convicted in the 1918 Chicago IWW mass trial. He received a twenty-year sentence and a $20,000 fine and spent periods in Cook County Jail and Leavenworth Penitentiary before his release on bond in 1919. Lossieff did not surrender after the appeal failed. He was one of nine defendants identified as bail jumpers in 1921, and later reporting placed him in Soviet Russia by 1925.',
    'Herbert McCutcheon' => 'Herbert McCutcheon was a former officer of the Western Federation of Miners and an Industrial Workers of the World member convicted in the 1918 Chicago IWW mass trial. He was imprisoned from August 30, 1918 until his release on bond on May 27, 1920. McCutcheon did not surrender after the appeal failed. He was one of the nine defendants identified in IWW records as having jumped bail and left for Soviet Russia in 1921.',
    'Grover H. Perry' => 'Grover H. Perry was an Industrial Workers of the World organizer and secretary-treasurer of the Metal Mine Workers Industrial Union in Salt Lake City. An outspoken opponent of U.S. participation in World War I, he was convicted in the 1918 Chicago IWW mass trial, sentenced to twelve years and fined $20,000. Perry was held in the Chicago House of Corrections and Leavenworth Penitentiary, where he developed pulmonary tuberculosis, before his release on bond in September 1919. He did not surrender after the appeal failed and was one of nine defendants identified in IWW records as having left for Soviet Russia in 1921.',
    'Charles Rothfiser' => 'Charles Rothfiser, also recorded as Charles Rothfisher, edited the IWW’s Hungarian-language newspaper in Chicago. Convicted in the 1918 Chicago IWW mass trial, he received a twenty-year sentence and a $20,000 fine and was held at Leavenworth Penitentiary until bond was posted in April 1919. Rothfiser did not surrender after the appeal failed. He was one of nine defendants identified in IWW records as having jumped bail and left for Soviet Russia in 1921.',
    'Fred Jaakkola' => 'Fred Jaakkola was a Detroit member of the Industrial Workers of the World convicted in the 1918 Chicago IWW mass trial. He received a ten-year sentence and a $30,000 fine and was held at Leavenworth Penitentiary until his release on bail in May 1919. Jaakkola did not surrender after the appeal failed. He was one of nine defendants identified in IWW records as having jumped bail and left for Soviet Russia in 1921.',
];

foreach ($updates as $name => $description) {
    $profile = Prisoner::withoutGlobalScopes()->where('name', $name)->sole();
    if (! $profile->in_exile || ! $profile->cases()->whereYear('in_exile_since', 1921)->exists()) {
        throw new RuntimeException("Exile guard failed for {$name}");
    }
}

if (! $apply) {
    echo json_encode(['ready' => true, 'profiles' => array_keys($updates)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$before = [];
DB::transaction(function () use ($updates, &$before) {
    foreach ($updates as $name => $description) {
        $profile = Prisoner::withoutGlobalScopes()->where('name', $name)->sole();
        $before[$name] = $profile->description;
        $profile->description = $description;
        $profile->save();
    }
});

$verified = collect($updates)->every(fn ($description, $name) => Prisoner::withoutGlobalScopes()->where('name', $name)->value('description') === $description);
if (! $verified) throw new RuntimeException('IWW prose verification failed');
Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['updated' => array_keys($updates), 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
