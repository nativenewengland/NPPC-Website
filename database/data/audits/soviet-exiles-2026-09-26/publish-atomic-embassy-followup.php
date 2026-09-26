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

function followupSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$seborerStudy = ['CIA Studies in Intelligence, “On the Trail of a Fourth Soviet Spy at Los Alamos”', 'https://www.cia.gov/resources/csi/static/Project-SOLO-and-Seborers.pdf'];
$people = [
    [
        'name' => 'Oscar Seborer', 'first_name' => 'Oscar', 'last_name' => 'Seborer', 'aka' => 'Godsend; Smith',
        'birthdate' => [1921, 6, 4], 'death_date' => [2015, 4, 23], 'gender' => 'Male', 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'], 'affiliation' => ['United States Army', 'Manhattan Project'],
        'description' => 'Oscar Seborer was an American electrical engineer and Army veteran who worked at Oak Ridge and Los Alamos during the Manhattan Project. Archival research later identified him as the Soviet source code-named “Godsend.” As the Rosenberg network was being exposed, Seborer, his brother Stuart, Stuart’s wife Miriam and her mother secretly left the United States for the Soviet bloc. They disappeared in Europe in 1952, lived near Dresden under the name Smith, and moved to Moscow in March 1957. Oscar remained in the Soviet Union and Russia until his death in Moscow in 2015.',
        'sources' => [$seborerStudy],
        'case' => ['charges' => 'Never prosecuted in the United States; later identified through FBI and KGB records as a Soviet atomic-espionage source.', 'in_exile_since' => [1952], 'end_of_exile' => [2015, 4, 23], 'sentence' => 'Secretly entered the Soviet bloc during the Rosenberg-era investigations, moved to Moscow in March 1957, and never returned to the United States.'],
    ],
    [
        'name' => 'Stuart Seborer', 'first_name' => 'Stuart', 'last_name' => 'Seborer', 'aka' => 'Solomon Seborer; Stewart Smith',
        'birthdate' => [1918], 'gender' => 'Male', 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'], 'affiliation' => ['United States Army', 'United States Department of the Treasury'],
        'description' => 'Stuart Seborer, born Solomon Seborer, was an American economist, wartime Army officer and former Treasury Department employee. KGB archival material later identified him as part of the same Soviet intelligence group as his brother Oscar. Stuart, Oscar, Stuart’s wife Miriam and her mother secretly disappeared into the Soviet bloc in 1952 as pressure mounted around the Rosenberg espionage investigation. They lived near Dresden as the Smith family before moving to Moscow in March 1957. Stuart used the name Stewart Smith and worked at the Soviet Academy of Sciences’ Institute of World Economy and International Relations.',
        'sources' => [$seborerStudy],
        'case' => ['charges' => 'Never prosecuted in the United States; later identified in KGB archival material as a member of a Soviet intelligence group.', 'in_exile_since' => [1952], 'sentence' => 'Secretly entered the Soviet bloc during the Rosenberg-era investigations and moved to Moscow in March 1957 under the name Stewart Smith.'],
    ],
    [
        'name' => 'Miriam Zeitlin Seborer', 'first_name' => 'Miriam', 'middle_name' => 'Zeitlin', 'last_name' => 'Seborer',
        'birthdate' => [1918], 'death_date' => [2002], 'gender' => 'Female', 'state' => 'New York', 'era' => '1950s',
        'ideologies' => [], 'affiliation' => ['United States Navy WAVES', 'United States Census Bureau'],
        'description' => 'Miriam Zeitlin Seborer was a physician, former Census Bureau employee and Navy WAVES veteran. She left the United States with her husband Stuart Seborer, his brother Oscar and her mother, and the group secretly entered the Soviet bloc in 1952. They lived near Dresden under the name Smith and moved to Moscow in March 1957. After Miriam tried to contact the U.S. Embassy and return home, Soviet authorities arrested and threatened her and sent her to Alma-Ata in what an FBI informant described as protective custody. Miriam and Stuart divorced in 1961. She was eventually permitted to leave with her son and mother on December 29, 1969.',
        'sources' => [$seborerStudy],
        'case' => ['charges' => 'No United States criminal charge located; Soviet authorities acted after she tried to contact the U.S. Embassy and leave.', 'in_exile_since' => [1952], 'end_of_exile' => [1969, 12, 29], 'sentence' => 'Arrested, threatened and internally exiled to Alma-Ata by Soviet authorities after seeking to return to the United States; permitted to leave on December 29, 1969.'],
    ],
    [
        'name' => 'George Koval', 'first_name' => 'George', 'last_name' => 'Koval', 'aka' => 'George Abramovich Koval; Delmar',
        'birthdate' => [1913, 12, 25], 'death_date' => [2006, 1, 31], 'gender' => 'Male', 'state' => 'Iowa', 'era' => '1940s',
        'ideologies' => ['Communism'], 'affiliation' => ['United States Army', 'Manhattan Project', 'GRU'],
        'description' => 'George Koval was an American-born chemist and Soviet military-intelligence officer who returned to the United States under his own name in 1940. Drafted into the U.S. Army, he gained access to Manhattan Project work at Oak Ridge and Dayton and passed information about nuclear materials and bomb initiators to the Soviet Union. In October 1948, as U.S. counterintelligence began connecting him to his family’s Soviet history, Koval sailed for Europe and never returned. He lived in Moscow for the rest of his life. Russia posthumously named him a Hero of the Russian Federation for his intelligence work.',
        'sources' => [
            ['Atomic Heritage Foundation, George Koval profile', 'https://ahf.nuclearmuseum.org/ahf/profile/george-koval/'],
            ['CIA Studies in Intelligence review of Sleeper Agent', 'https://www.cia.gov/resources/csi/static/Studies_in_Intelligence_65_4_Unclass_Extracts_December_2021_Updated.pdf'],
            ['Library of Congress study of American Jewish migration to Birobidzhan', 'https://tile.loc.gov/storage-services/master/gdc/gdcebookspublic/20/19/66/79/67/2019667967/2019667967.pdf'],
        ],
        'case' => ['charges' => 'Never prosecuted in the United States; later confirmed as a Soviet military-intelligence officer who passed Manhattan Project information.', 'in_exile_since' => [1948, 10], 'end_of_exile' => [2006, 1, 31], 'sentence' => 'Left the United States for Europe in October 1948 as counterintelligence scrutiny approached and lived in Moscow until his death.'],
    ],
    [
        'name' => 'Anatoly P. Kotloby', 'first_name' => 'Anatoly', 'middle_name' => 'P.', 'last_name' => 'Kotloby', 'aka' => 'Anatole P. Kotloby; Anatoly Kotlobai; Cook',
        'gender' => 'Male', 'state' => 'New Jersey', 'era' => '1960s',
        'ideologies' => ['Communism'], 'affiliation' => ['Thiokol Chemical Corporation'],
        'description' => 'Anatoly P. Kotloby was a Soviet-born American chemical engineer who worked on classified solid-rocket propellants at Thiokol in New Jersey. Former KGB officers later identified him as the agent code-named Cook, recruited in 1959 to provide rocket-fuel formulas. The FBI detained and questioned Kotloby in 1964 but released him for lack of evidence. He and his wife immediately flew to Moscow before another arrest could be made. After he criticized life in the Soviet Union, the KGB accused him of being a CIA double agent. Unable to prove that allegation, authorities entrapped him in a currency-exchange case and sentenced him to eight years in prison. He remained in Russia after his release.',
        'sources' => [
            ['American Scientist, “Rocket Science and Russian Spies”', 'https://www.researchgate.net/publication/215507622_Rocket_Science_and_Russian_Spies'],
            ['National Archives memorandum identifying Kotloby as a KGB agent', 'https://www.archives.gov/files/research/jfk/releases/docid-32129553.pdf'],
        ],
        'case' => ['charges' => 'Questioned by the FBI over suspected espionage; later prosecuted in the Soviet Union in a currency-exchange sting after an unsupported accusation that he was a CIA double agent.', 'in_exile_since' => [1964], 'sentence' => 'Fled to Moscow after FBI questioning and was later sentenced by Soviet authorities to eight years in prison.'],
    ],
    [
        'name' => 'James McMillin', 'first_name' => 'James', 'last_name' => 'McMillin',
        'gender' => 'Male', 'era' => '1940s', 'ideologies' => ['Anti-Capitalism'], 'affiliation' => ['United States Army', 'United States Embassy Moscow'],
        'description' => 'James McMillin was a young U.S. Army cryptographer assigned to the American Embassy in Moscow. On May 15, 1948, the day his two-year tour was due to end, he told his father that he would remain in the Soviet Union as a protest against the anti-Soviet policies of the people he described as America’s capitalist rulers. Embassy officials believed his attachment to Galina Dunaeva Biconish, the estranged wife of another American sergeant, was the more immediate reason. McMillin married and raised a family in Moscow and did not return to the United States.',
        'sources' => [
            ['Susan L. Carruthers, Cold War Captives, chapter excerpt', 'https://content.ucpress.edu/chapters/11204.ch01.pdf'],
            ['Interview with McMillin’s son about the family’s Soviet life', 'https://podcasts.apple.com/fr/podcast/166-my-father-defected-to-russia-for-love-with/id1567302778?i=1000670351767'],
        ],
        'case' => ['charges' => 'Military desertion from an embassy cryptographic assignment; no completed U.S. prosecution located.', 'in_exile_since' => [1948, 5, 15], 'sentence' => 'Refused repatriation at the end of his Moscow assignment and remained in the Soviet Union.'],
    ],
    [
        'name' => 'John Discoe Smith', 'first_name' => 'John', 'middle_name' => 'Discoe', 'last_name' => 'Smith',
        'birthdate' => [1926], 'gender' => 'Male', 'state' => 'Massachusetts', 'era' => '1960s',
        'ideologies' => [], 'affiliation' => ['United States Department of State'],
        'description' => 'John Discoe Smith was a State Department communications technician and code clerk who served at the U.S. Embassy in New Delhi from 1955 to 1959. He resigned from the department in December 1959 and disappeared from public view by mid-1960. Soviet authorities revealed in October 1967 that he was living in the USSR. Articles and a book published under Smith’s name alleged that he had performed work for the CIA in India and implicated the agency in covert operations. U.S. officials denied the claims, and contemporary reporting described his departure as rooted in personal instability rather than high politics.',
        'sources' => [
            ['National Archives counterintelligence review of the Smith case', 'https://documents3.theblackvault.com/documents/jfkfiles/jfk2022/124-10365-10020.pdf'],
            ['CIA FOIA copy of contemporary New York Times reporting', 'https://www.cia.gov/readingroom/document/cia-rdp75-00149r000700290005-2'],
            ['Study of Cold War asylum politics in India', 'https://nottingham-repository.worktribe.com/preview/4269750/MCGARR%202%20-%20Politics%20of%20Cold%20War%20Asylum%20in%20India.pdf'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge located; resigned from the State Department and later surfaced in Soviet propaganda alleging CIA misconduct.', 'in_exile_since' => [1960], 'sentence' => 'Disappeared abroad by mid-1960 and was publicly identified in 1967 as an American defector living in the Soviet Union.'],
    ],
    [
        'name' => 'Annabelle Bucar', 'first_name' => 'Annabelle', 'last_name' => 'Bucar', 'aka' => 'Annabel Bucar; Annabelle Lapshina-Bucar',
        'birthdate' => [1915, 2, 7], 'death_date' => [1998], 'gender' => 'Female', 'state' => 'Pennsylvania', 'era' => '1940s',
        'ideologies' => [], 'affiliation' => ['Office of Strategic Services', 'United States Department of State'],
        'description' => 'Annabelle Bucar was a former Office of Strategic Services employee and administrative assistant in the U.S. Embassy’s information office in Moscow. She resigned in February 1948 and said she would remain in the Soviet Union because embassy policy was incompatible with her favorable view of the Soviet people; she also acknowledged that her relationship with Russian singer Konstantin Lapshin influenced the decision. Bucar received Soviet citizenship and became a Moscow radio announcer. A 1949 book issued under her name attacked American diplomacy and alleged embassy espionage, although later researchers questioned how much of it Soviet authorities wrote. She remained in Moscow until her death in 1998.',
        'sources' => [
            ['U.S. Department of State historical report on Bucar’s resignation and book', 'https://history.state.gov/historicaldocuments/frus1949v05/d335'],
            ['Susan L. Carruthers, Cold War Captives, chapter excerpt', 'https://content.ucpress.edu/chapters/11204.ch01.pdf'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge located; resigned from her embassy position after rejecting U.S. policy toward the Soviet Union.', 'in_exile_since' => [1948, 2], 'end_of_exile' => [1998], 'sentence' => 'Remained in the Soviet Union, received Soviet citizenship, and worked in state broadcasting.'],
    ],
    [
        'name' => 'Orest Stephen Makar', 'first_name' => 'Orest', 'middle_name' => 'Stephen', 'last_name' => 'Makar',
        'gender' => 'Male', 'state' => 'Missouri', 'era' => '1950s', 'ideologies' => [], 'affiliation' => ['Saint Louis University', 'White Sands Missile Range'],
        'description' => 'Orest Stephen Makar was a Ukrainian-born photogrammetrist who entered the United States as a displaced person in 1949, taught engineering at Saint Louis University, and later worked at the White Sands missile proving ground. He became a naturalized American citizen in 1956, then renounced that citizenship and returned to the Soviet Union in December 1956. American officials alleged that he had been a Soviet agent during his U.S. residence, though the public record located for this profile does not establish a prosecution. In the USSR he taught geodesy at the Polytechnic Institute in Lviv.',
        'sources' => [
            ['Congressional Record account of Makar’s return to the Soviet Union', 'https://www.congress.gov/85/crecb/1958/01/22/GPO-CRECB-1958-pt1-10-2.pdf'],
            ['TIME archive, “Education: The Defector”', 'https://web.archive.org/web/20081214024219/http://www.time.com/time/magazine/article/0,9171,867607,00.html'],
        ],
        'case' => ['charges' => 'Publicly accused of acting as a Soviet agent while in the United States; no completed U.S. prosecution located.', 'in_exile_since' => [1956, 12], 'sentence' => 'Renounced recently acquired U.S. citizenship and returned to the Soviet Union, where he taught in Lviv.'],
    ],
];

$names = array_column($people, 'name');
$duplicates = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->pluck('name');
if ($duplicates->isNotEmpty()) throw new RuntimeException('Identity guard: profiles already exist: '.$duplicates->implode(', '));

if (! $apply) {
    echo json_encode(['ready' => true, 'profiles_to_create' => count($people), 'names' => $names], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-atomic-embassy-soviet-exiles-'.gmdate('Ymd-His').'.sqlite');
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
            'under_review' => false, 'body' => followupSources($sources),
        ], $spec));
        if ($birth) $profile->setPartialDate('birthdate', ...$birth);
        if ($death) $profile->setPartialDate('death_date', ...$death);
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
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
    && $verify->every(fn ($profile) => $profile->in_exile && ! $profile->currently_in_exile && ! $profile->under_review && $profile->cases->count() === 1 && $profile->cases->first()->in_exile_since);
if (! $ok) throw new RuntimeException('Post-publication verification failed');
echo json_encode(['backup' => $backup, 'created' => $created, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
