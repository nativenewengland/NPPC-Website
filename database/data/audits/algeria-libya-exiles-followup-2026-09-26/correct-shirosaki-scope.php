<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
$profile = Prisoner::withoutGlobalScopes()->where('id', '86c4af51-08e4-451c-9359-d7b7553bdf08')->with('cases')->sole();
$case = $profile->cases->firstWhere('id', 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5');

if ($profile->name !== 'Tsutomu Shirosaki'
    || ! $profile->in_exile
    || $profile->currently_in_exile
    || ! $case
    || $case->partialDateIso('in_exile_since') !== '1977-10-03'
    || $case->partialDateIso('end_of_exile') !== '1996-09-21') {
    throw new RuntimeException('Shirosaki correction guard failed');
}

if (! $apply) {
    echo json_encode([
        'ready' => true,
        'correction' => 'Remove U.S.-exile classification and duration; retain corrected U.S. case dates.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-shirosaki-exile-scope-correction-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

DB::transaction(function () use ($profile, $case) {
    $profile->in_exile = false;
    $profile->currently_in_exile = false;
    $profile->description = str_replace(
        'On September 21, 1996, authorities turned him over to the FBI, ending almost nineteen years abroad.',
        'On September 21, 1996, authorities turned him over to the FBI after almost nineteen years abroad. Because that period followed release from Japanese custody, it is not classified here as exile from U.S. custody or prosecution.',
        $profile->description
    );
    $profile->save();

    $precision = $case->date_precision ?? [];
    unset($precision['in_exile_since'], $precision['end_of_exile']);
    DB::table('prisoner_cases')->where('id', $case->id)->update([
        'in_exile_since' => null,
        'end_of_exile' => null,
        'in_exile_for_days' => null,
        'date_precision' => $precision ? json_encode($precision) : null,
        'updated_at' => now(),
    ]);
});

Cache::forget(PrisonerApiController::cacheKey());

$profile = Prisoner::withoutGlobalScopes()->where('id', '86c4af51-08e4-451c-9359-d7b7553bdf08')->with('cases')->sole();
$case = $profile->cases->firstWhere('id', 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5');
$ok = ! $profile->in_exile
    && ! $profile->currently_in_exile
    && ! $case->in_exile_since
    && ! $case->end_of_exile
    && $case->in_exile_for_days === null
    && $case->partialDateIso('sentenced_date') === '1998-02-20'
    && $case->partialDateIso('release_date') === '2015-01-16';
if (! $ok) {
    throw new RuntimeException('Post-correction verification failed');
}

echo json_encode(['backup' => $backup, 'corrected' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
