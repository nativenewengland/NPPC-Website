<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\PrisonerSortOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);
$variants = ['Alexander Kazem-Bek', 'Aleksandr Kazem-Bek', 'Alexander Kazembek', 'Alexandre Kasem-Beg'];
$existing = Prisoner::withoutGlobalScopes()
    ->where(function ($query) use ($variants) {
        foreach ($variants as $variant) {
            $query->orWhere('name', $variant)->orWhere('aka', 'like', '%'.$variant.'%');
        }
    })
    ->get(['id', 'name', 'aka', 'slug']);

if ($existing->isNotEmpty()) {
    throw new RuntimeException('Identity guard: possible existing profile: '.$existing->toJson(JSON_UNESCAPED_UNICODE));
}

if (! $apply) {
    echo json_encode(['ready' => true, 'profile_to_create' => 'Alexander Kazem-Bek'], JSON_PRETTY_PRINT).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-kazem-bek-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$created = DB::transaction(function () {
    $profile = new Prisoner();
    $profile->fill([
        'name' => 'Alexander Kazem-Bek',
        'first_name' => 'Alexander',
        'last_name' => 'Kazem-Bek',
        'aka' => 'Aleksandr Lvovich Kazem-Bek; Alexander Kazembek; Alexandre Kasem-Beg',
        'gender' => 'Male',
        'state' => 'Connecticut',
        'era' => '1950s',
        'ideologies' => [],
        'affiliation' => ['Yale University', 'Connecticut College', 'Russian Orthodox Church'],
        'in_custody' => false,
        'released' => false,
        'in_exile' => true,
        'currently_in_exile' => false,
        'awaiting_trial' => false,
        'under_review' => false,
        'description' => 'Alexander Kazem-Bek was a Russian-born political activist, writer and Orthodox church worker who lived and taught in the United States after escaping wartime Europe. He taught Russian at Yale and later headed Russian language and literature work at Connecticut College. Kazem-Bek applied in New Delhi in 1954 for permission to return permanently to the Soviet Union and left the United States at the end of 1956, initially without his American wife and children. In a January 1957 letter published by Pravda, he described his fifteen years in the United States as forced residence and said anti-Soviet campaigning and what he regarded as the country’s moral decline drove his return. Contemporary and later accounts also describe the move as voluntary repatriation by a Russian émigré; he was not an American-born exile. He received Soviet citizenship, worked for the Moscow Patriarchate and remained in Moscow until his death in 1977.',
        'body' => '<h2>Sources</h2><ul>'
            .'<li><a href="https://oregonnews.uoregon.edu/lccn/sn99063813/1957-03-24/ed-1/seq-7/ocr/">Associated Press report on Kazem-Bek’s return and Soviet writings, March 24, 1957</a></li>'
            .'<li><a href="https://manoa.hawaii.edu/library/wp-content/uploads/2021/07/Russia-Collection-Report-Jul-2020-to-Jun-2021.pdf">University of Hawaiʻi Russian collection report and biographical note</a></li>'
            .'<li><a href="https://search.worldcat.org/title/Aleksandr-Kazem-Bek-papers-1930-1977/oclc/614009718">Aleksandr Kazem-Bek papers, Columbia University collection description</a></li>'
            .'</ul>',
    ]);
    $profile->setPartialDate('birthdate', 1902, 2, 15);
    $profile->setPartialDate('death_date', 1977, 2, 21);
    $profile->save();

    $case = new PrisonerCase(['prisoner_id' => $profile->id]);
    $case->charges = 'No United States criminal charge located; returned to the Soviet Union after publicly rejecting American anti-Soviet politics and social conditions.';
    $case->sentence = 'Left the United States at the end of 1956, received Soviet citizenship, and remained in Moscow until his death.';
    $case->setPartialDate('in_exile_since', 1956);
    $case->setPartialDate('end_of_exile', 1977, 2, 21);
    $case->save();

    $placed = PrisonerSortOrder::place($profile, $profile->era);
    return ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
});

Cache::forget(PrisonerApiController::cacheKey());
$verify = Prisoner::withoutGlobalScopes()->where('name', 'Alexander Kazem-Bek')->with('cases')->firstOrFail();
$ok = $verify->in_exile && ! $verify->currently_in_exile && ! $verify->under_review
    && $verify->cases->count() === 1 && $verify->cases->first()->in_exile_since;
if (! $ok) {
    throw new RuntimeException('Post-publication verification failed');
}

echo json_encode(['backup' => $backup, 'created' => $created, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
