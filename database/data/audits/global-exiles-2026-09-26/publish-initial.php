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

function globalExileSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$chinaCommonSources = [
    ['U.S. Army Korean War chronology', 'https://www.army.mil/koreanwar/'],
    ['National Film Board of Canada, “They Chose China”', 'https://www.nfb.ca/film/they_chose_china/'],
    ['Adam J. Zweiback, “The 21 Turncoat GIs”', 'https://doi.org/10.1111/j.1540-6563.1998.tb01398.x'],
];

$china = [
    ['Clarence Adams', 'Clarence', null, 'Adams', 'Tennessee', null, [1999], null,
        'Clarence Adams was a U.S. Army corporal from Memphis and one of 21 American Korean War prisoners who refused repatriation. He cited racism in the United States, studied communist political theory while captive and crossed into China with the group on February 24, 1954. In China he married and later made Radio Hanoi broadcasts urging Black American soldiers to oppose the Vietnam War. He returned to the United States in 1966 as the Cultural Revolution made China increasingly hostile to foreigners. The House Un-American Activities Committee subpoenaed him but did not question him publicly.'],
    ['Howard Gayle Adams', 'Howard', 'Gayle', 'Adams', 'Texas', null, null, null,
        'Howard Gayle Adams was a U.S. Army sergeant from Corsicana, Texas, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China with the group on February 24, 1954 and worked at a paper factory in Jinan. He consistently declined interviews. Later reporting said he had returned to the United States and may subsequently have gone back to China; the end of his exile is not firmly documented.'],
    ['Albert Constant Belhomme', 'Albert', 'Constant', 'Belhomme', null, null, null, [1964],
        'Albert Constant Belhomme was a Belgian-born U.S. Army sergeant who had emigrated to the United States as a teenager. Captured during the Korean War, he became one of 21 American servicemen who declined repatriation and crossed into China on February 24, 1954. He worked in a Jinan paper factory and lived in China for about ten years before returning to Antwerp, Belgium.'],
    ['Otho Grayson Bell', 'Otho', 'Grayson', 'Bell', 'Washington', null, [2003], [1955, 7],
        'Otho Grayson Bell was a U.S. Army corporal from Olympia, Washington, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and was assigned to a collective farm with William Cowart and Lewis Griggs. The three returned to the United States in July 1955. Authorities arrested them on arrival but released them after determining that their dishonorable discharges had ended military jurisdiction.'],
    ['Richard Gordon', 'Richard', null, 'Gordon', 'Illinois', null, [1988], [1958, 1],
        'Richard Gordon was a U.S. Army sergeant from Chicago and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and returned to the United States in January 1958. Contemporary accounts reported that he continued to support communism after his return.'],
    ['William Cowart', 'William', null, 'Cowart', 'Georgia', null, null, [1955, 7],
        'William Cowart was a U.S. Army corporal from Dalton, Georgia, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and was assigned to a collective farm with Otho Bell and Lewis Griggs. They returned together in July 1955, were arrested and then released because their dishonorable discharges had ended military jurisdiction. The three later won a Supreme Court case for back pay owed through the date of discharge.'],
    ['Rufus Douglas', 'Rufus', null, 'Douglas', null, null, [1954], [1954],
        'Rufus Douglas was a U.S. Army sergeant and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954. Douglas died in China only a few months later; accounts describe the death as probably natural but do not establish an exact date or cause.'],
    ['John Roedel Dunn', 'John', 'Roedel', 'Dunn', 'Pennsylvania', [1928, 6, 29], [1996], [1959, 12],
        'John Roedel Dunn was a U.S. Army corporal from Altoona, Pennsylvania, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954. Dunn married a Czechoslovak woman while in China and moved with her to Czechoslovakia in December 1959, continuing his exile outside the United States. He died in Slovakia in 1996.'],
    ['Andrew Fortuna', 'Andrew', null, 'Fortuna', 'Kentucky', null, [1984], [1957, 7, 3],
        'Andrew Fortuna was a U.S. Army sergeant from Greenup, Kentucky, who received two Bronze Stars before his capture in Korea. He became one of 21 American prisoners who refused repatriation and crossed into China on February 24, 1954. Fortuna returned to the United States on July 3, 1957.'],
    ['Lewis Wayne Griggs', 'Lewis', 'Wayne', 'Griggs', 'Texas', null, [1984], [1955, 7],
        'Lewis Wayne Griggs was a U.S. Army soldier and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and was assigned to a collective farm with Otho Bell and William Cowart. The three returned in July 1955, were arrested and then released because their dishonorable discharges had ended military jurisdiction. Griggs later earned a sociology degree from Stephen F. Austin State University.'],
    ['Samuel David Hawkins', 'Samuel', 'David', 'Hawkins', 'Oklahoma', null, null, [1957, 2],
        'Samuel David Hawkins was a U.S. Army private first class from Oklahoma City and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, married a Russian woman there and returned to the United States in February 1957. He successfully petitioned to have his dishonorable discharge changed to an other-than-honorable discharge.'],
    ['Arlie Pate', 'Arlie', null, 'Pate', null, null, [1999], [1956],
        'Arlie Pate was a U.S. Army corporal and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and worked in a paper mill. Pate returned to the United States with Aaron Wilson in 1956.'],
    ['Scott Rush', 'Scott', null, 'Rush', null, null, null, [1964],
        'Scott Rush was a U.S. Army sergeant and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, married there and lived in the country for roughly ten years. Rush and his wife then moved to the United States and settled in the Midwest.'],
    ['Lowell Skinner', 'Lowell', null, 'Skinner', null, null, [1995], [1963],
        'Lowell Skinner was a U.S. Army corporal and one of 21 American Korean War prisoners who refused repatriation despite public appeals from his mother. He crossed into China on February 24, 1954, married there and returned alone to the United States in 1963.'],
    ['LaRance Sullivan', 'LaRance', null, 'Sullivan', null, null, [2001], [1958],
        'LaRance Sullivan was a U.S. Army soldier and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and returned to the United States in 1958.'],
    ['Richard Tenneson', 'Richard', null, 'Tenneson', 'Utah', null, [2001], [1955],
        'Richard Tenneson was a U.S. Army private first class and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and returned to the United States in 1955, later settling in Utah.'],
    ['James Veneris', 'James', null, 'Veneris', 'Pennsylvania', [1922], [2004], [2004],
        'James Veneris was a U.S. Army private from Vandergrift, Pennsylvania, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, adopted the Chinese name Lao Wen and spent the rest of his life there. He worked in a steel mill, married and raised a family. Veneris visited the United States in 1976 but returned to China, where he died in 2004.'],
    ['Harold Webb', 'Harold', null, 'Webb', 'Florida', null, null, [1988],
        'Harold Webb was a U.S. Army sergeant from Jacksonville, Florida, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, married a Polish woman and moved to Poland in 1960. His exile from the United States continued until he received permission to resettle there in 1988.'],
    ['William White', 'William', null, 'White', null, null, null, [1965],
        'William White was a U.S. Army corporal and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, married and earned a degree in international law there. White returned to the United States in 1965.'],
    ['Morris Wills', 'Morris', null, 'Wills', 'New York', null, [1999], [1965],
        'Morris Wills was a U.S. Army corporal from Fort Ann, New York, and one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954, attended Peking University and married there. Wills returned to the United States in 1965, worked at Harvard University and published the memoir Turncoat about his twelve years in China.'],
    ['Aaron Wilson', 'Aaron', null, 'Wilson', 'Louisiana', [1932], [2014], [1956, 12, 6],
        'Aaron Wilson was a U.S. Army corporal from Urania, Louisiana, who was captured in 1950 and became one of 21 American Korean War prisoners who refused repatriation. He crossed into China on February 24, 1954 and returned to the United States on December 6, 1956. He later said that his youth, limited schooling and years of captivity shaped the decision to remain.'],
];

$people = [];
foreach ($china as $row) {
    [$name, $first, $middle, $last, $state, $birth, $death, $end, $description] = $row;
    $people[] = [
        'name' => $name, 'first_name' => $first, 'middle_name' => $middle,
        'last_name' => $last, 'state' => $state, 'birthdate' => $birth,
        'death_date' => $death, 'era' => '1950s', 'gender' => 'Male',
        'affiliation' => ['United States Army'], 'ideologies' => [],
        'description' => $description, 'sources' => $chinaCommonSources,
        'case' => [
            'charges' => 'Declined repatriation to the United States after captivity in the Korean War; no criminal conviction resulted from that decision.',
            'in_exile_since' => [1954, 2, 24], 'end_of_exile' => $end,
            'sentence' => 'Dishonorably discharged by the U.S. Army after refusing repatriation and permitted to leave the neutral custody area for China.',
        ],
    ];
}

$people = array_merge($people, [
    [
        'name' => 'Terry Marvell Whitmore', 'first_name' => 'Terry', 'middle_name' => 'Marvell', 'last_name' => 'Whitmore',
        'aka' => 'Terry Whitmore', 'state' => 'Tennessee', 'birthdate' => [1947, 3, 6], 'death_date' => [2007, 7, 11],
        'era' => '1960s', 'gender' => 'Male', 'race' => 'Black', 'affiliation' => ['United States Marine Corps', 'American Deserters Committee'],
        'ideologies' => ['Anti-war'],
        'description' => 'Terry Marvell Whitmore was a Black U.S. Marine from Memphis who was wounded in Vietnam and decorated by President Lyndon Johnson. While recovering in Japan, he concluded that the war was immoral and refused orders sending him back to Vietnam. With help from the Japanese anti-war network Beheiren, he traveled through the Soviet Union and arrived in Sweden on May 27, 1968, where he received refuge as a military deserter. Whitmore publicly connected his resistance to the war, atrocities he had witnessed and racism in the military and the United States. He published Memphis-Nam-Sweden: The Autobiography of a Black American Exile in 1971. After a visit to the United States in 1977, he remained in Sweden until returning permanently to Memphis in 2001.',
        'sources' => [
            ['Congressional Record, “American Deserters in Sweden”', 'https://www.govinfo.gov/content/pkg/GPO-CRECB-1969-pt26/pdf/GPO-CRECB-1969-pt26-3-1.pdf'],
            ['The New Yorker, “Out of It”', 'https://www.newyorker.com/magazine/1970/05/23/out-of-it'],
            ['The Commercial Appeal obituary', 'https://www.legacy.com/us/obituaries/commercialappeal/name/terry-whitmore-obituary?id=15760698'],
        ],
        'case' => [
            'charges' => 'Deserted the U.S. Marine Corps rather than return to combat in Vietnam; a deserter was exposed to military prosecution and imprisonment if returned.',
            'in_exile_since' => [1968, 5, 27], 'end_of_exile' => [2001],
            'sentence' => 'Received refuge in Sweden and remained there for more than three decades; no completed U.S. court-martial located.',
        ],
    ],
    [
        'name' => 'Donald Lee Cox', 'first_name' => 'Donald', 'middle_name' => 'Lee', 'last_name' => 'Cox',
        'aka' => 'Donald L. Cox; Field Marshal DC; Don Cox', 'state' => 'Missouri',
        'birthdate' => [1936, 4, 16], 'death_date' => [2011, 2, 19], 'era' => '1970s',
        'gender' => 'Male', 'race' => 'Black', 'affiliation' => ['Black Panther Party'],
        'ideologies' => ['Black liberation'],
        'description' => 'Donald Lee Cox, known as Don Cox or Field Marshal DC, was a national leader of the Black Panther Party. Prosecutors accused him and other Panthers of conspiring to murder Eugene Anderson, a Baltimore Panther identified as a police informant. Cox denied responsibility and left the United States rather than stand trial, joining the Black Panther international section in Algeria around 1970. He later moved to southern France and never returned permanently to the United States. Cox died in Camps-sur-l’Agly on February 19, 2011 after roughly four decades in exile. The charge remained unresolved because he was never tried.',
        'sources' => [
            ['TIME obituary, “D.L. Cox”', 'https://time.com/archive/6595460/d-l-cox/'],
            ['TIME, excerpt from Cox’s memoir', 'https://time.com/5527603/don-cox-black-panther-party/'],
            ['Heyday Books author biography', 'https://www.heydaybooks.com/authors/don-cox/'],
        ],
        'case' => [
            'charges' => 'Conspiracy in the killing of Black Panther member and police informant Eugene Anderson in Baltimore; Cox was never tried.',
            'in_exile_since' => [1970], 'end_of_exile' => [2011, 2, 19],
            'sentence' => 'Fled first to Algeria and later lived in France until his death; no conviction or sentence was imposed.',
        ],
    ],
    [
        'name' => 'Larry Allen Abshier', 'first_name' => 'Larry', 'middle_name' => 'Allen', 'last_name' => 'Abshier',
        'state' => 'Illinois', 'birthdate' => [1943], 'death_date' => [1983, 7, 11], 'era' => '1960s',
        'gender' => 'Male', 'affiliation' => ['United States Army'], 'ideologies' => [],
        'description' => 'Larry Allen Abshier was a U.S. Army private stationed in South Korea who left his post and crossed the demilitarized zone into North Korea on May 28, 1962. The evidence does not establish a developed political motive before his crossing. North Korea used Abshier and three later American deserters in propaganda, English instruction and films. Accounts from Charles Jenkins describe the men as closely controlled, subjected to compulsory ideological study and unable to leave. Abshier remained in North Korea until his death from a heart attack in Pyongyang on July 11, 1983.',
        'sources' => [
            ['U.S. Congressional Record account of the 1962 crossings', 'https://www.govinfo.gov/content/pkg/GPO-CRECB-1963-pt10/pdf/GPO-CRECB-1963-pt10-10.pdf'],
            ['TIME, “In From the Cold”', 'https://time.com/2818022/in-from-the-cold/'],
            ['Columbia Journalism Review overview of the American defectors', 'https://www.cjr.org/the_media_today/american_soldiers_north_korea_movies_travis_king_defection.php'],
        ],
        'case' => [
            'charges' => 'Deserted the U.S. Army and crossed from South Korea into North Korea.',
            'in_exile_since' => [1962, 5, 28], 'end_of_exile' => [1983, 7, 11],
            'sentence' => 'No U.S. prosecution occurred; he remained under North Korean control until his death.',
        ],
    ],
    [
        'name' => 'James Joseph Dresnok', 'first_name' => 'James', 'middle_name' => 'Joseph', 'last_name' => 'Dresnok',
        'aka' => 'Joe Dresnok', 'state' => 'Virginia', 'birthdate' => [1941, 11, 24], 'death_date' => [2016, 11],
        'era' => '1960s', 'gender' => 'Male', 'affiliation' => ['United States Army'], 'ideologies' => [],
        'description' => 'James Joseph Dresnok was a U.S. Army private first class stationed on the Korean demilitarized zone. Facing military discipline for forging a pass and going absent without leave, he crossed a minefield into North Korea on August 15, 1962. He later described the act as an escape from his collapsed marriage and military life rather than an ideological decision. North Korea used Dresnok in propaganda, English instruction and films. In 1966 he and the three other American deserters tried to seek asylum at the Soviet embassy, which returned them to North Korean authorities. Dresnok remained in Pyongyang until his death from a stroke in November 2016.',
        'sources' => [
            ['U.S. Congressional Record account of Dresnok’s crossing', 'https://www.govinfo.gov/content/pkg/GPO-CRECB-1963-pt10/pdf/GPO-CRECB-1963-pt10-10.pdf'],
            ['University of Cambridge, “Crossing The Line”', 'https://www.cam.ac.uk/news/crossing-the-line-the-story-of-comrade-joe'],
            ['TIME, “In From the Cold”', 'https://time.com/2818022/in-from-the-cold/'],
        ],
        'case' => [
            'charges' => 'Faced summary court-martial for forged leave papers and absence without leave, then deserted into North Korea.',
            'in_exile_since' => [1962, 8, 15], 'end_of_exile' => [2016, 11],
            'sentence' => 'No U.S. prosecution occurred; he remained under North Korean control until his death.',
        ],
    ],
    [
        'name' => 'Jerry Wayne Parrish', 'first_name' => 'Jerry', 'middle_name' => 'Wayne', 'last_name' => 'Parrish',
        'aka' => 'Kim Yu-il', 'state' => 'Kentucky', 'birthdate' => [1944, 3, 10], 'death_date' => [1998, 8, 25],
        'era' => '1960s', 'gender' => 'Male', 'affiliation' => ['United States Army'], 'ideologies' => [],
        'description' => 'Jerry Wayne Parrish was a U.S. Army corporal from Morganfield, Kentucky, who crossed the demilitarized zone into North Korea on December 6, 1963. Charles Jenkins later wrote that Parrish described personal and family reasons for leaving rather than an ideological commitment. North Korea gave him the name Kim Yu-il and used him and the other American deserters in propaganda and films. Parrish remained in Pyongyang until his death on August 25, 1998 after longstanding kidney illness.',
        'sources' => [
            ['Washington Post record of American defectors in North Korea', 'https://www.washingtonpost.com/archive/opinions/1997/05/16/for-the-record/d1b13b4e-0610-4c48-94c2-43a19795ca30/'],
            ['TIME, “In From the Cold”', 'https://time.com/2818022/in-from-the-cold/'],
            ['Columbia Journalism Review overview of the American defectors', 'https://www.cjr.org/the_media_today/american_soldiers_north_korea_movies_travis_king_defection.php'],
        ],
        'case' => [
            'charges' => 'Deserted the U.S. Army and crossed from South Korea into North Korea.',
            'in_exile_since' => [1963, 12, 6], 'end_of_exile' => [1998, 8, 25],
            'sentence' => 'No U.S. prosecution occurred; he remained under North Korean control until his death.',
        ],
    ],
    [
        'name' => 'Charles Robert Jenkins', 'first_name' => 'Charles', 'middle_name' => 'Robert', 'last_name' => 'Jenkins',
        'state' => 'North Carolina', 'birthdate' => [1940, 2, 18], 'death_date' => [2017, 12, 11],
        'era' => '1960s', 'gender' => 'Male', 'affiliation' => ['United States Army'], 'ideologies' => [],
        'description' => 'Charles Robert Jenkins was a U.S. Army sergeant from North Carolina who left a patrol and crossed into North Korea on January 5, 1965. He later said he feared dangerous patrols and deployment to Vietnam and mistakenly expected the Soviet Union to send him home. North Korea instead held him for nearly four decades, subjected him to forced ideological study and used him in propaganda and films. Jenkins left North Korea in July 2004 to reunite with his wife Hitomi Soga in Indonesia and Japan. He surrendered to U.S. military authorities, pleaded guilty to desertion and aiding the enemy on November 3, 2004, received a dishonorable discharge and a 30-day sentence, and was released after serving 25 days. He remained in Japan until his death.',
        'sources' => [
            ['Associated Press overview of Jenkins’s case', 'https://apnews.com/article/travis-king-americans-north-korea-detained-715d3fd4c85d5457237624e5110452c0'],
            ['TIME, “In From the Cold”', 'https://time.com/2818022/in-from-the-cold/'],
            ['Los Angeles Times report on the court-martial', 'https://www.latimes.com/archives/la-xpm-2004-nov-03-fg-jenkins3-story.html'],
        ],
        'case' => [
            'charges' => 'Desertion and aiding the enemy; additional solicitation and disloyalty counts were resolved through a plea agreement.',
            'in_exile_since' => [1965, 1, 5], 'end_of_exile' => [2004, 7],
            'incarceration_date' => [2004, 11, 3], 'documented_imprisoned_for_days' => 25,
            'sentence' => 'Pleaded guilty on November 3, 2004; sentenced to 30 days, reduced to private, dishonorably discharged and released after 25 days for good behavior.',
        ],
    ],
]);

// A short existing record under “Don Cox” was found during preflight. Update it
// in place rather than creating the formal-name version above as a duplicate.
$people = array_values(array_filter($people, fn ($spec) => $spec['name'] !== 'Donald Lee Cox'));

$names = array_column($people, 'name');
$duplicateNames = array_merge($names, ['Terry Whitmore', 'Charles Jenkins', 'James Dresnok', 'Larry Abshier', 'Jerry Parrish']);
$duplicates = Prisoner::withoutGlobalScopes()->whereIn('name', $duplicateNames)->get(['id', 'name', 'aka']);
if ($duplicates->isNotEmpty()) {
    throw new RuntimeException('Identity guard: possible existing profiles: '.$duplicates->toJson(JSON_UNESCAPED_UNICODE));
}

$don = Prisoner::withoutGlobalScopes()->where('name', 'Don Cox')->with('cases')->firstOrFail();
$kathleen = Prisoner::withoutGlobalScopes()->where('name', 'Kathleen Cleaver')->with('cases')->firstOrFail();
$eldridge = Prisoner::withoutGlobalScopes()->where('name', 'Eldridge Cleaver')->firstOrFail();
if ($don->cases->count() !== 1 || $kathleen->cases->count() !== 1) {
    throw new RuntimeException('Existing-profile case-count guard failed');
}

if (! $apply) {
    echo json_encode([
        'ready' => true, 'count_to_create' => count($people), 'profiles_to_create' => $names,
        'profiles_to_update' => [$don->name, $kathleen->name, $eldridge->name],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-global-exiles-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$result = DB::transaction(function () use ($people, $don, $kathleen, $eldridge) {
    $created = [];
    foreach ($people as $spec) {
        $caseSpec = $spec['case'];
        $sources = $spec['sources'];
        $birth = $spec['birthdate'] ?? null;
        $death = $spec['death_date'] ?? null;
        unset($spec['case'], $spec['sources'], $spec['birthdate'], $spec['death_date']);

        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false, 'released' => false, 'in_exile' => true,
            'currently_in_exile' => false, 'awaiting_trial' => false,
            'under_review' => false, 'body' => globalExileSources($sources),
        ], array_filter($spec, fn ($value) => $value !== null)));
        if ($birth) {
            $profile->setPartialDate('birthdate', ...$birth);
        }
        if ($death) {
            $profile->setPartialDate('death_date', ...$death);
        }
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
        foreach (['charges', 'sentence', 'documented_imprisoned_for_days'] as $field) {
            if (array_key_exists($field, $caseSpec)) {
                $case->{$field} = $caseSpec[$field];
            }
        }
        foreach (['in_exile_since', 'end_of_exile', 'incarceration_date'] as $field) {
            if (! empty($caseSpec[$field])) {
                $case->setPartialDate($field, ...$caseSpec[$field]);
            }
        }
        $case->save();

        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }
    $don->body = globalExileSources([
        ['TIME obituary, “D.L. Cox”', 'https://time.com/archive/6595460/d-l-cox/'],
        ['TIME, excerpt from Cox’s memoir', 'https://time.com/5527603/don-cox-black-panther-party/'],
        ['Heyday Books author biography', 'https://www.heydaybooks.com/authors/don-cox/'],
    ]);
    $don->state = 'Missouri';
    $don->middle_name = trim((string) $don->middle_name);
    $don->save();

    $kathleen->body = globalExileSources([
        ['National Archives biography of Kathleen Cleaver', 'https://www.archives.gov/research/african-americans/individuals/kathleen-cleaver'],
        ['University of Texas human-rights biography', 'https://law.utexas.edu/humanrights/directory/kathleen-neal-cleaver/'],
        ['PBS Frontline interview with Kathleen Cleaver', 'https://www.pbs.org/wgbh/pages/frontline/shows/race/interviews/kcleaver.html'],
    ]);
    $kathleen->save();
    $kathleenCase = $kathleen->cases->first();
    $kathleenCase->setPartialDate('in_exile_since', 1969);
    $kathleenCase->setPartialDate('end_of_exile', 1975);
    $kathleenCase->save();

    $eldridge->body = globalExileSources([
        ['National Archives biography of Eldridge Cleaver', 'https://www.archives.gov/research/african-americans/individuals/eldridge-cleaver'],
        ['PBS Frontline interview with Eldridge Cleaver', 'https://www.pbs.org/wgbh/pages/frontline/shows/race/interviews/ecleaver2.html'],
        ['Online Archive of California, Eldridge Cleaver papers', 'https://oac.cdlib.org/findaid/ark%3A%2F13030%2Fkt6000263k'],
    ]);
    $eldridge->save();

    return ['created' => $created, 'updated' => [$don->name, $kathleen->name, $eldridge->name]];
});

Cache::forget(PrisonerApiController::cacheKey());
$verify = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get();
$ok = $verify->count() === count($names)
    && $verify->every(fn ($profile) => $profile->in_exile && ! $profile->currently_in_exile && ! $profile->under_review
        && $profile->cases->contains(fn ($case) => (bool) $case->in_exile_since));
if (! $ok) {
    throw new RuntimeException('Post-publication verification failed');
}

echo json_encode(['backup' => $backup, 'result' => $result, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
