<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Institution;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\PrisonerSortOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);

function laterSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($s) => '<li><a href="'.htmlspecialchars($s[1], ENT_QUOTES).'">'.htmlspecialchars($s[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$hsca = ['U.S. House Select Committee on Assassinations, The Defector Study', 'https://www.aarclibrary.org/publib/jfk/hsca/reportvols/vol12/pdf/HSCA_Vol12_DefectorStudy.pdf'];
$people = [
    [
        'name' => 'Bruce Frederick Davis', 'first_name' => 'Bruce', 'middle_name' => 'Frederick', 'last_name' => 'Davis',
        'birthdate' => [1936, 5, 4], 'gender' => 'Male', 'state' => 'New York', 'era' => '1960s',
        'ideologies' => ['Anti-Militarism'], 'affiliation' => ['United States Army'],
        'description' => 'Bruce Frederick Davis was a U.S. Army soldier who left his post in West Germany in August 1960 after about five years of service. He spent a month in East Germany and then entered the Soviet Union. Articles in Pravda and Izvestia quoted Davis describing his defection as the result of disillusionment with U.S. foreign and military policy. Soviet authorities settled him in Kyiv as a student. Davis later repeatedly contacted the U.S. Embassy seeking documents to leave. He returned to West Germany and U.S. military control in July 1963.',
        'sources' => [$hsca, ['National Archives table of American defectors', 'https://documents3.theblackvault.com/documents/jfkfiles/NARA-Oct2017/ARRB/ESCHEINK/WP-DOCS/TABLEDEF.WPD.pdf']],
        'case' => ['charges' => 'Desertion from a U.S. Army post in West Germany.', 'in_exile_since' => [1960, 8], 'end_of_exile' => [1963, 7], 'sentence' => 'Defected through East Germany to the Soviet Union and returned to U.S. military control in July 1963.'],
    ],
    [
        'name' => 'Joseph Dutkanicz', 'first_name' => 'Joseph', 'last_name' => 'Dutkanicz',
        'birthdate' => [1926, 6, 9], 'death_date' => [1963, 11], 'gender' => 'Male', 'era' => '1960s',
        'ideologies' => [], 'affiliation' => ['United States Army'],
        'description' => 'Joseph Dutkanicz was a U.S. Army sergeant stationed in West Germany who later told American officials that Soviet intelligence had recruited him in 1958 through threats and inducements. As a Western security investigation approached him in June 1960, Dutkanicz took his wife and three children through Czechoslovakia into the Soviet Union with KGB assistance. Soviet media used his case for anti-American broadcasts, and authorities settled the family in Lviv. By March 1962 he was disillusioned and asked the U.S. Embassy to help him return regardless of possible charges. He died in Lviv in November 1963 before repatriation.',
        'sources' => [$hsca, ['National Archives table of American defectors', 'https://documents3.theblackvault.com/documents/jfkfiles/NARA-Oct2017/ARRB/ESCHEINK/WP-DOCS/TABLEDEF.WPD.pdf']],
        'case' => ['charges' => 'Subject of a Western security investigation after recruitment by Soviet intelligence while serving in the U.S. Army.', 'in_exile_since' => [1960, 6], 'end_of_exile' => [1963, 11], 'sentence' => 'Left West Germany through Czechoslovakia for Soviet Ukraine; asked to return in 1962 but died before repatriation.'],
    ],
    [
        'name' => 'Vladimir Sloboda', 'first_name' => 'Vladimir', 'last_name' => 'Sloboda',
        'birthdate' => [1927, 1, 7], 'gender' => 'Male', 'era' => '1960s',
        'ideologies' => ['Anti-Militarism'], 'affiliation' => ['United States Army', '513th Military Intelligence Group'],
        'description' => 'Vladimir Sloboda was a naturalized U.S. citizen assigned to the Army’s 513th Military Intelligence Group in Frankfurt. In August 1960 he crossed into East Germany and requested Soviet asylum while Army counterintelligence activity was disrupting intelligence networks in Europe. Sloboda later said that he had been blackmailed and framed into defecting. Soviet television and press presented statements attributed to him describing the United States as a warmonger and criticizing American espionage in Germany. He received Soviet citizenship and lived in Lviv under continued KGB questioning.',
        'sources' => [$hsca],
        'case' => ['charges' => 'Defection from a U.S. Army military-intelligence assignment amid counterintelligence activity; no completed U.S. prosecution located.', 'in_exile_since' => [1960, 8], 'sentence' => 'Crossed into East Germany, requested Soviet asylum, and received Soviet citizenship.'],
    ],
    [
        'name' => 'Victor Norris Hamilton', 'first_name' => 'Victor', 'middle_name' => 'Norris', 'last_name' => 'Hamilton', 'aka' => 'Fouzi Demitri Hindaly; Victor Nornilson',
        'birthdate' => [1919, 7, 15], 'death_date' => [1997, 11, 11], 'gender' => 'Male', 'era' => '1960s',
        'ideologies' => [], 'affiliation' => ['National Security Agency'],
        'description' => 'Victor Norris Hamilton, born Fouzi Demitri Hindaly, was a naturalized American and former National Security Agency Arabic cryptanalyst. The NSA dismissed him in 1959 after a psychiatric evaluation. Hamilton traveled through Prague to the Soviet Union in 1962, and Izvestia published disclosures attributed to him about NSA work in July 1963. Soviet authorities confined him in psychiatric institutions and transferred him in 1971 to Special Psychiatric Hospital No. 5 near Moscow. A humanitarian group rediscovered him there in May 1992 after more than twenty years. His family had believed he was dead. Hamilton remained in Russia and died in 1997.',
        'sources' => [
            ['NSA, American Cryptology during the Cold War, Book II', 'https://www.nsa.gov/portals/75/documents/news-features/declassified-documents/cryptologic-histories/cold_war_ii.pdf'],
            ['Congressional Record account of Hamilton’s confinement', 'https://www.govinfo.gov/content/pkg/GPO-CRECB-1992-pt22/pdf/GPO-CRECB-1992-pt22-1-3.pdf'],
            ['UPI report on Hamilton’s rediscovery, June 5, 1992', 'https://www.upi.com/Archives/1992/06/05/US-defector-found-confined-in-Russian-psychiatric-hospital/5685707716800/'],
        ],
        'case' => ['charges' => 'No U.S. criminal case located; defected after dismissal from the National Security Agency.', 'in_exile_since' => [1962], 'end_of_exile' => [1997, 11, 11], 'incarceration_date' => [1971], 'institution' => ['Special Psychiatric Hospital No. 5', 'Troitskoye', 'Russia'], 'sentence' => 'Confined in Soviet psychiatric institutions and held at Special Psychiatric Hospital No. 5 from 1971 until at least his rediscovery in May 1992.'],
    ],
    [
        'name' => 'Harold M. Koch', 'first_name' => 'Harold', 'middle_name' => 'M.', 'last_name' => 'Koch',
        'birthdate' => [1932], 'gender' => 'Male', 'state' => 'Illinois', 'era' => '1960s',
        'ideologies' => ['Anti-War', 'Vietnam War Resistance'],
        'description' => 'Harold M. Koch was a former Roman Catholic priest from Chicago who sought asylum in the Soviet Union in 1966. In a Soviet television appearance, Koch said that his defection was a protest against the Vietnam War and the Johnson administration’s policies. Soviet authorities provided him an apartment in Moscow. After roughly three months, Koch decided to return to the United States, explaining that he wanted to marry. He left the Soviet Union in December 1966.',
        'sources' => [
            ['Contemporary cases summarized in The New Republic', 'https://newrepublic.com/article/113757/snowden-case-unhappy-history-american-defectors-moscow'],
            ['Harold Koch biographical source list and contemporary press references', 'https://en.wikipedia.org/wiki/Harold_M._Koch'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge located; requested Soviet asylum as a protest against the Vietnam War.', 'in_exile_since' => [1966, 9], 'end_of_exile' => [1966, 12, 19], 'sentence' => 'Lived in Moscow for about three months before returning voluntarily to the United States.'],
    ],
    [
        'name' => 'Theodore Branch', 'first_name' => 'Theodore', 'last_name' => 'Branch',
        'gender' => 'Male', 'state' => 'Pennsylvania', 'era' => '1980s',
        'ideologies' => ['Socialism'], 'affiliation' => [],
        'description' => 'Theodore Branch and his wife Cheryl traveled from Pennsylvania to the Soviet Union as tourists in November 1987 and asked to remain. They said Soviet law, order and social equality offered an alternative to capitalism. The Presidium of the Supreme Soviet granted them political asylum in January 1988. The couple worked on English-language radio broadcasts in Moscow. They returned to the United States in September 1988 after less than a year, then asked the Soviet Embassy to let them return when they became dissatisfied with conditions at home; that request was unsuccessful.',
        'sources' => [
            ['Los Angeles Times, January 19, 1988', 'https://www.latimes.com/archives/la-xpm-1988-01-19-mn-37163-story.html'],
            ['UPI, October 4, 1988', 'https://www.upi.com/Archives/1988/10/04/US-couple-wants-to-return-to-Russia/3995591940800/'],
            ['Study of Soviet political-asylum practice', 'https://refugee.ru/wp-content/uploads/2022/07/troitsky-on-political-asylum.pdf'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge; requested Soviet asylum as an ideological rejection of capitalism.', 'in_exile_since' => [1987, 11], 'end_of_exile' => [1988, 9], 'sentence' => 'Granted Soviet political asylum in January 1988 and returned to the United States in September 1988.'],
    ],
    [
        'name' => 'Cheryl Branch', 'first_name' => 'Cheryl', 'last_name' => 'Branch',
        'gender' => 'Female', 'state' => 'Pennsylvania', 'era' => '1980s',
        'ideologies' => ['Socialism'], 'affiliation' => [],
        'description' => 'Cheryl Branch and her husband Theodore traveled from Pennsylvania to the Soviet Union as tourists in November 1987 and asked to remain. They said Soviet law, order and social equality offered an alternative to capitalism. The Presidium of the Supreme Soviet granted them political asylum in January 1988. The couple worked on English-language radio broadcasts in Moscow. They returned to the United States in September 1988 after less than a year, then asked the Soviet Embassy to let them return when they became dissatisfied with conditions at home; that request was unsuccessful.',
        'sources' => [
            ['Los Angeles Times, January 19, 1988', 'https://www.latimes.com/archives/la-xpm-1988-01-19-mn-37163-story.html'],
            ['UPI, October 4, 1988', 'https://www.upi.com/Archives/1988/10/04/US-couple-wants-to-return-to-Russia/3995591940800/'],
            ['Study of Soviet political-asylum practice', 'https://refugee.ru/wp-content/uploads/2022/07/troitsky-on-political-asylum.pdf'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge; requested Soviet asylum as an ideological rejection of capitalism.', 'in_exile_since' => [1987, 11], 'end_of_exile' => [1988, 9], 'sentence' => 'Granted Soviet political asylum in January 1988 and returned to the United States in September 1988.'],
    ],
];

$names = array_column($people, 'name');
$duplicates = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->pluck('name');
if ($duplicates->isNotEmpty()) throw new RuntimeException('Identity guard: profiles already exist: '.$duplicates->implode(', '));

if (! $apply) {
    echo json_encode(['ready' => true, 'profiles_to_create' => count($people), 'names' => $names], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-later-soviet-exiles-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$created = DB::transaction(function () use ($people) {
    $created = [];
    foreach ($people as $spec) {
        $caseSpec = $spec['case']; $sources = $spec['sources'];
        $birth = $spec['birthdate'] ?? null; $death = $spec['death_date'] ?? null;
        unset($spec['case'], $spec['sources'], $spec['birthdate'], $spec['death_date']);
        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false, 'released' => false, 'in_exile' => true,
            'currently_in_exile' => false, 'awaiting_trial' => false,
            'under_review' => false, 'body' => laterSources($sources),
        ], $spec));
        if ($birth) $profile->setPartialDate('birthdate', ...$birth);
        if ($death) $profile->setPartialDate('death_date', ...$death);
        $profile->save();

        $institutionId = null;
        if (! empty($caseSpec['institution'])) {
            [$name, $city, $state] = $caseSpec['institution'];
            $institutionId = Institution::firstOrCreate(['name' => $name], ['city' => $city, 'state' => $state])->id;
        }
        $case = new PrisonerCase(['prisoner_id' => $profile->id, 'institution_id' => $institutionId]);
        foreach (['charges', 'convicted', 'sentence'] as $field) if (isset($caseSpec[$field])) $case->{$field} = $caseSpec[$field];
        foreach (['arrest_date', 'incarceration_date', 'release_date', 'in_exile_since', 'end_of_exile'] as $field) {
            if (! empty($caseSpec[$field])) $case->setPartialDate($field, ...$caseSpec[$field]);
        }
        $case->save();
        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }
    return $created;
});

Cache::forget(PrisonerApiController::cacheKey());
$verify = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get();
$ok = $verify->count() === count($people)
    && $verify->every(fn ($p) => $p->in_exile && ! $p->currently_in_exile && ! $p->under_review && $p->cases->count() === 1 && $p->cases->first()->in_exile_since);
if (! $ok) throw new RuntimeException('Post-publication verification failed');
echo json_encode(['backup' => $backup, 'created' => $created, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
