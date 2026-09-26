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

function ExileSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$expected = [
    'Edwin P. Wilson' => [
        'id' => 'bdeced46-9c35-4acb-b2fb-f2ed11aa0ec5',
        'case_id' => '39912565-9a8d-4ca1-871f-69ac0836db0a',
    ],
    'Tsutomu Shirosaki' => [
        'id' => '86c4af51-08e4-451c-9359-d7b7553bdf08',
        'case_id' => 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5',
    ],
];

$profiles = Prisoner::withoutGlobalScopes()->whereIn('name', array_keys($expected))->with('cases')->get()->keyBy('name');
if ($profiles->count() !== count($expected)) {
    throw new RuntimeException('Profile guard failed: '.json_encode($profiles->keys()->all()));
}

foreach ($expected as $name => $ids) {
    $profile = $profiles[$name];
    if ($profile->id !== $ids['id'] || $profile->in_exile || $profile->currently_in_exile) {
        throw new RuntimeException("Profile-state guard failed for {$name}");
    }
    $case = $profile->cases->firstWhere('id', $ids['case_id']);
    if (! $case || $case->in_exile_since || $case->end_of_exile) {
        throw new RuntimeException("Case-state guard failed for {$name}");
    }
}

if (! $apply) {
    echo json_encode([
        'ready' => true,
        'profiles_to_update' => array_keys($expected),
        'corrections' => [
            'Edwin P. Wilson' => 'Add April 23, 1980–June 15, 1982 Libya fugitive-exile span and case outcome.',
            'Tsutomu Shirosaki' => 'Repair arrest, sentence and release dates without classifying his post-Japanese-custody residence as U.S. exile.',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-algeria-libya-exiles-followup-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

DB::transaction(function () use ($profiles) {
    $wilson = $profiles['Edwin P. Wilson'];
    $wilson->in_exile = true;
    $wilson->currently_in_exile = false;
    $wilson->description = 'Edwin Paul Wilson was a former CIA and Office of Naval Intelligence officer who became a private international arms dealer. A federal grand jury indicted him on April 23, 1980 for exporting explosives to Libya, acting as an unregistered Libyan agent and conspiring to solicit the murder of a Libyan dissident. Wilson remained outside the United States and lived in a villa near Tripoli while a fugitive. Federal agents induced him to leave Libya in June 1982; Dominican authorities refused him entry and placed him on a flight to New York, where he was arrested on June 15. Wilson received consecutive federal sentences totaling fifty-two years for firearms and explosives exports and for later attempts to arrange murders while jailed. A federal judge vacated the Texas explosives conviction in 2003 after finding that prosecutors had used a CIA affidavit they knew was false. His other convictions remained, and he was released on September 14, 2004 after twenty-two years in custody. He died on September 10, 2012.';
    $wilson->body = ExileSources([
        ['U.S. Justice Department case study giving the April 23, 1980 indictment and charges', 'https://ojp.gov/pdffiles1/Digitization/153249NCJRS.pdf'],
        ['Fourth Circuit decision documenting Wilson’s refuge in Libya and June 15, 1982 arrest', 'https://law.counselstack.com/opinion/united-states-v-edwin-paul-wilson-ca4-1983'],
        ['Washington Post report on the operation that lured Wilson from his villa outside Tripoli', 'https://www.washingtonpost.com/archive/politics/1982/06/16/ex-cia-agent-edwin-wilson-lured-from-libya-arrested/6c670cff-0f69-4769-9feb-65766eae0303/'],
        ['Federal decision vacating Wilson’s Texas explosives conviction', 'https://law.justia.com/cases/federal/district-courts/FSupp2/289/801/2430886/'],
        ['Associated Press obituary summarizing the convictions, vacatur, release and death', 'https://www.cbsnews.com/news/former-cia-operative-edwin-wilson-dies-at-84/'],
    ]);
    $wilson->save();

    $wilsonCase = $wilson->cases->firstWhere('id', '39912565-9a8d-4ca1-871f-69ac0836db0a');
    $wilsonCase->charges = 'Unlicensed export of firearms and explosives to Libya; acting as an unregistered Libyan agent; conspiracy and solicitation to commit murder; later obstruction, witness tampering and attempted murder charges.';
    $wilsonCase->sentence = 'Arrested June 15, 1982 after two years as a fugitive in Libya. Federal sentences imposed in 1982–83 totaled fifty-two years. The seventeen-year Texas explosives conviction was vacated in 2003 because prosecutors used a CIA affidavit they knew was false; other convictions remained. Released September 14, 2004 after twenty-two years in custody.';
    $wilsonCase->setPartialDate('in_exile_since', 1980, 4, 23);
    $wilsonCase->setPartialDate('end_of_exile', 1982, 6, 15);
    $wilsonCase->setPartialDate('arrest_date', 1982, 6, 15);
    $wilsonCase->setPartialDate('incarceration_date', 1982, 6, 15);
    $wilsonCase->setPartialDate('sentenced_date', 1982, 12, 21);
    $wilsonCase->setPartialDate('release_date', 2004, 9, 14);
    $wilsonCase->save();

    $shirosaki = $profiles['Tsutomu Shirosaki'];
    $shirosaki->in_exile = false;
    $shirosaki->currently_in_exile = false;
    $shirosaki->save();

    $shirosakiCase = $shirosaki->cases->firstWhere('id', 'b6cc81f8-75c8-45d2-aa5b-386d7e0a1df5');
    $shirosakiCase->charges = 'Attempted murder and assault of U.S. embassy personnel, attempted damage to a U.S. embassy and a violent attack on internationally protected personnel for the May 14, 1986 Jakarta rocket attack.';
    $shirosakiCase->sentence = 'Thirty years in federal prison, imposed February 20, 1998 after a November 14, 1997 conviction. Shirosaki denied responsibility. Released January 16, 2015 and deported to Japan the following month.';
    $shirosakiCase->setPartialDate('arrest_date', 1996, 9, 21);
    $shirosakiCase->setPartialDate('incarceration_date', 1996, 9, 21);
    $shirosakiCase->setPartialDate('sentenced_date', 1998, 2, 20);
    $shirosakiCase->setPartialDate('release_date', 2015, 1, 16);
    $shirosakiCase->save();
});

Cache::forget(PrisonerApiController::cacheKey());

$verify = Prisoner::withoutGlobalScopes()->whereIn('name', array_keys($expected))->with('cases')->get()->keyBy('name');
$wilsonCase = $verify['Edwin P. Wilson']->cases->firstWhere('id', $expected['Edwin P. Wilson']['case_id']);
$shirosakiCase = $verify['Tsutomu Shirosaki']->cases->firstWhere('id', $expected['Tsutomu Shirosaki']['case_id']);
$ok = $verify->count() === 2
    && $verify['Edwin P. Wilson']->in_exile
    && ! $verify['Edwin P. Wilson']->currently_in_exile
    && ! $verify['Tsutomu Shirosaki']->in_exile
    && ! $verify['Tsutomu Shirosaki']->currently_in_exile
    && $verify->every(fn ($profile) => ! $profile->under_review)
    && $wilsonCase->partialDateIso('in_exile_since') === '1980-04-23'
    && $wilsonCase->partialDateIso('end_of_exile') === '1982-06-15'
    && $shirosakiCase->partialDateIso('in_exile_since') === null
    && $shirosakiCase->partialDateIso('end_of_exile') === null
    && $shirosakiCase->partialDateIso('sentenced_date') === '1998-02-20';
if (! $ok) {
    throw new RuntimeException('Post-publication verification failed');
}

echo json_encode([
    'backup' => $backup,
    'updated' => array_keys($expected),
    'verified' => true,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
