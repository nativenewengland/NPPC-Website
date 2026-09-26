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

function modernSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$people = [
    [
        'name' => 'John Anthony Robles II',
        'first_name' => 'John',
        'middle_name' => 'Anthony',
        'last_name' => 'Robles',
        'aka' => 'John Robles',
        'birthdate' => [1966, 4, 10],
        'gender' => 'Male',
        'state' => 'Puerto Rico',
        'era' => '2000s',
        'ideologies' => [],
        'affiliation' => ['Voice of Russia'],
        'description' => 'John Anthony Robles II is a Puerto Rico-born American journalist, broadcaster and English teacher who moved to Russia with his children in 1996. He later worked for the state broadcaster Voice of Russia. In 2007, the United States declined to renew his passport under a law permitting denial when the government certifies substantial child-support arrears. Robles disputed the debt and said the action was retaliation for his political publishing and opposition to U.S. foreign policy. Russia issued him a refugee certificate, leaving him in Russia without a valid U.S. passport. Independent reporting confirms the refugee status but does not substantiate his broader allegations of CIA persecution. President Vladimir Putin granted Robles Russian citizenship on October 21, 2024.',
        'sources' => [
            ['Columbia Law Review, “Fleeing the Land of the Free”', 'https://columbialawreview.org/wp-content/uploads/2023/01/Rathod-Fleeing_the_land_of_the_free.pdf'],
            ['The Insider investigation of foreign pro-Kremlin propagandists', 'https://theins.press/en/society/266830'],
            ['Russian presidential citizenship decree, October 21, 2024', 'https://publication.pravo.gov.ru/document/0001202410210004?index=1'],
        ],
        'case' => [
            'charges' => 'No criminal prosecution located; the United States declined to renew his passport after certifying child-support arrears, which Robles disputed and characterized as political retaliation.',
            'in_exile_since' => [2007],
            'sentence' => 'Received Russian refugee status after the passport denial and remained in Russia; granted Russian citizenship on October 21, 2024.',
        ],
    ],
    [
        'name' => 'John Mark Dougan',
        'first_name' => 'John',
        'middle_name' => 'Mark',
        'last_name' => 'Dougan',
        'aka' => 'BadVolf',
        'birthdate' => [1976, 12, 15],
        'gender' => 'Male',
        'state' => 'Florida',
        'era' => '2010s',
        'ideologies' => [],
        'affiliation' => ['United States Marine Corps', 'Palm Beach County Sheriff’s Office'],
        'description' => 'John Mark Dougan is a former U.S. Marine and Palm Beach County sheriff’s deputy who created anonymous sites publishing accusations and personal information about Florida law-enforcement officials. After the FBI and local police searched his home in March 2016 during a computer-crime investigation, Dougan traveled through Canada and left for Russia. He said he feared retaliation by officials he had accused of corruption. Florida prosecutors later charged him with 21 counts involving extortion and illegal recording. Russia granted him temporary asylum in February 2017 and made the status permanent in December 2018. Dougan remained in Moscow and became a prominent producer of pro-Kremlin disinformation; later allegations that he worked with Russian military intelligence are recorded as findings by Western authorities and investigators, not as adjudicated facts in his original asylum case.',
        'sources' => [
            ['Daily Beast investigation of Dougan’s flight and asylum', 'https://www.thedailybeast.com/fugitive-cop-says-hes-behind-the-dnc-leaks-its-his-latest-hoax/'],
            ['Washington Post investigation of Dougan’s later Russian operations', 'https://www.washingtonpost.com/world/2024/10/23/dougan-russian-disinformation-harris/'],
            ['European Union sanctions regulation identifying Dougan', 'https://eur-lex.europa.eu/legal-content/EN/TXT/PDF/?uri=OJ%3AL_202502568'],
        ],
        'case' => [
            'charges' => 'Twenty-one Florida state counts involving extortion and illegal wiretapping or recording, filed after his departure.',
            'in_exile_since' => [2016],
            'sentence' => 'Fled after a March 2016 search, received temporary Russian asylum in February 2017 and permanent status in December 2018; the Florida charges remain unresolved.',
        ],
    ],
    [
        'name' => 'Tara Reade',
        'first_name' => 'Tara',
        'last_name' => 'Reade',
        'aka' => 'Alexandra Tara McCabe',
        'birthdate' => [1964, 2, 26],
        'gender' => 'Female',
        'state' => 'California',
        'era' => '2020s',
        'ideologies' => [],
        'affiliation' => ['United States Senate', 'RT'],
        'description' => 'Tara Reade, legally named Alexandra Tara McCabe, is a former junior Senate aide who accused then-senator Joe Biden of sexually assaulting her in 1993; Biden denied the allegation. Reade moved to Russia in May 2023 and said she feared imprisonment, surveillance and threats by the U.S. government because of her allegation and pro-Russian political speech. The White House called her claim that the government endangered her “absolutely false,” and no U.S. criminal charge against Reade was located. She subsequently worked for the Russian state-funded broadcaster RT. Reade said she received asylum, and President Vladimir Putin granted her Russian citizenship on September 22, 2025. Her profile records the competing claims without treating her allegations of government persecution as established fact.',
        'sources' => [
            ['TIME report on Reade’s move and the White House response', 'https://time.com/6283951/tara-reade-biden-russia-absolutely-false/'],
            ['Russian presidential citizenship decree No. 670, September 22, 2025', 'https://publication.pravo.gov.ru/document/0001202509220040'],
            ['Report identifying her legal name, birth date and citizenship grant', 'https://www.channelstv.com/2025/09/22/putin-grants-citizenship-to-us-woman-who-accused-biden-of-sexual-assault/'],
        ],
        'case' => [
            'charges' => 'No United States criminal charge located; claimed government surveillance, threats and possible imprisonment, allegations the White House denied.',
            'in_exile_since' => [2023, 5],
            'sentence' => 'Moved to Russia in May 2023, later said she received asylum, and was granted Russian citizenship on September 22, 2025.',
        ],
    ],
];

$newNames = array_column($people, 'name');
$duplicates = Prisoner::withoutGlobalScopes()
    ->whereIn('name', array_merge($newNames, ['John Robles']))
    ->orWhere('aka', 'like', '%John Anthony Robles%')
    ->orWhere('aka', 'like', '%John Mark Dougan%')
    ->orWhere('aka', 'like', '%Tara Reade%')
    ->get(['id', 'name', 'aka']);
if ($duplicates->isNotEmpty()) {
    throw new RuntimeException('Identity guard: possible existing profiles: '.$duplicates->toJson(JSON_UNESCAPED_UNICODE));
}

$snowden = Prisoner::withoutGlobalScopes()->where('name', 'Edward Snowden')->with('cases')->firstOrFail();
if ($snowden->cases->count() !== 1) {
    throw new RuntimeException('Edward Snowden case-count guard failed');
}

if (! $apply) {
    echo json_encode([
        'ready' => true,
        'profiles_to_create' => $newNames,
        'profile_to_update' => ['name' => $snowden->name, 'id' => $snowden->id],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-modern-russia-exiles-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$result = DB::transaction(function () use ($people, $snowden) {
    $created = [];
    foreach ($people as $spec) {
        $caseSpec = $spec['case'];
        $sources = $spec['sources'];
        $birth = $spec['birthdate'];
        unset($spec['case'], $spec['sources'], $spec['birthdate']);

        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false,
            'released' => false,
            'in_exile' => true,
            'currently_in_exile' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'body' => modernSources($sources),
        ], $spec));
        $profile->setPartialDate('birthdate', ...$birth);
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
        $case->charges = $caseSpec['charges'];
        $case->sentence = $caseSpec['sentence'];
        $case->setPartialDate('in_exile_since', ...$caseSpec['in_exile_since']);
        $case->save();
        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }

    $snowden->description = 'Edward Joseph Snowden is a former National Security Agency contractor who disclosed classified records to journalists in 2013, revealing the scale of United States government surveillance programs. He left Hawaii for Hong Kong on May 20, 2013. Federal prosecutors filed a sealed criminal complaint on June 14 charging theft of government property, unauthorized communication of national-defense information and willful communication of classified communications intelligence. Snowden flew to Moscow on June 23 while seeking passage to Latin America, but the United States had revoked his passport and he remained in the airport transit area for 39 days. Russia granted him temporary asylum on August 1, 2013, permanent residency in October 2020 and citizenship on September 26, 2022. He retained U.S. citizenship and remains wanted on the pending American charges; he has never been arrested, booked or imprisoned on them.';
    $snowden->body = modernSources([
        ['U.S. Department of Justice statement and criminal-charge timeline', 'https://www.justice.gov/archives/opa/pr/justice-department-statement-request-hong-kong-edward-snowden-s-provisional-arrest'],
        ['Columbia Law Review, “Fleeing the Land of the Free”', 'https://columbialawreview.org/wp-content/uploads/2023/01/Rathod-Fleeing_the_land_of_the_free.pdf'],
        ['Associated Press report on Russian citizenship', 'https://ny1.com/nyc/queens/ap-top-news/2022/09/26/putin-grants-russian-citizenship-to-edward-snowden'],
    ]);
    $snowden->save();

    return ['created' => $created, 'updated' => ['name' => $snowden->name, 'slug' => $snowden->slug, 'id' => $snowden->id]];
});

Cache::forget(PrisonerApiController::cacheKey());
$verify = Prisoner::withoutGlobalScopes()->whereIn('name', array_merge($newNames, ['Edward Snowden']))->with('cases')->get();
$ok = $verify->count() === 4
    && $verify->every(fn ($profile) => $profile->in_exile && $profile->currently_in_exile && ! $profile->under_review && $profile->cases->contains(fn ($case) => (bool) $case->in_exile_since));
if (! $ok) {
    throw new RuntimeException('Post-publication verification failed');
}

echo json_encode(['backup' => $backup, 'result' => $result, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
