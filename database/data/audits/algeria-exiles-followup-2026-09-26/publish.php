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

function AlgeriaSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$newPeople = [
    [
        'name' => 'Byron Vaughn Booth', 'first_name' => 'Byron', 'middle_name' => 'Vaughn', 'last_name' => 'Booth',
        'aka' => 'Byron Muezi Booth; Jo Jo', 'gender' => 'Male', 'race' => 'Black', 'era' => '1960s',
        'affiliation' => ['Black Panther Party'], 'ideologies' => ['Black liberation'],
        'released' => true,
        'description' => 'Byron Vaughn Booth and fellow prisoner Clinton Robert Smith Jr. escaped from the California Institution for Men at Chino on January 27, 1969, while serving robbery sentences. The next day they hijacked National Airlines Flight 64 to Cuba. Cuban authorities rejected U.S. demands for their return, and Booth and Smith later went to Algiers, where they joined Eldridge Cleaver and the emerging international section of the Black Panther Party. Booth left Algeria after Smith’s death and lived principally in Nigeria for the next three decades. Nigerian authorities returned him to the United States on January 26, 2001. He pleaded guilty to assaulting a flight-crew member and received a twelve-year federal sentence. Booth was out of prison by 2016 and later participated in an oral-history project about his life.',
        'sources' => [
            ['U.S. Department of Justice account of the Booth-Smith escape, hijacking, Algeria flight and Booth’s return', 'https://www.govinfo.gov/content/pkg/GOVPUB-Y4_J89_1-PURL-LPS42543/pdf/GOVPUB-Y4_J89_1-PURL-LPS42543.pdf'],
            ['Los Angeles Times report on Booth’s plea agreement and exile chronology', 'https://www.latimes.com/archives/la-xpm-2001-may-17-me-64627-story.html'],
            ['Washington Post report on Booth’s May 17, 2001 sentence', 'https://www.washingtonpost.com/archive/politics/2001/05/18/nation-in-brief/77a52906-d2af-43a1-b4ab-613ca27e2fdc/'],
            ['California State University Northridge oral-history excerpt with Booth', 'https://library.csun.edu/virtual-exhibit/PP/documents/elevating-byron-booth-transcript.pdf'],
        ],
        'case' => [
            'charges' => 'January 1969 escape from the California Institution for Men and hijacking of National Airlines Flight 64; later pleaded guilty to assault with a deadly weapon upon a flight-crew member.',
            'sentence' => 'Lived as a fugitive abroad for 32 years. Returned from Nigeria on January 26, 2001 and was sentenced on May 17, 2001 to twelve years in federal prison.',
            'in_exile_since' => [1969, 1, 28], 'end_of_exile' => [2001, 1, 26],
            'arrest_date' => [2001, 1, 26], 'sentenced_date' => [2001, 5, 17], 'incarceration_date' => [2001, 1, 26],
        ],
    ],
    [
        'name' => 'Clinton Robert Smith Jr.', 'first_name' => 'Clinton', 'middle_name' => 'Robert', 'last_name' => 'Smith Jr.',
        'aka' => 'Clinton R. Smith; Rahim Smith; Rahim', 'gender' => 'Male', 'race' => 'Black', 'era' => '1960s',
        'affiliation' => ['Black Panther Party'], 'ideologies' => ['Black liberation'],
        'description' => 'Clinton Robert “Rahim” Smith Jr. and fellow prisoner Byron Vaughn Booth escaped from the California Institution for Men at Chino on January 27, 1969, while Smith was serving a five-year sentence for second-degree robbery. The next day they hijacked National Airlines Flight 64 to Cuba and resisted U.S. efforts to return them. Cuba subsequently sent the two men to Algeria, where they joined Eldridge Cleaver and the group that became the Black Panther Party’s International Section. The historical record of Smith’s death rests on multiple later accounts: Elaine Mokhtefi wrote that Cleaver confessed to killing Smith in Algiers in November 1969, that Algerian authorities found a body, and that Booth witnessed and helped bury it. Because no public civil record has been located, the profile records the death at month precision and identifies the evidentiary basis rather than presenting an exact day.',
        'sources' => [
            ['U.S. Department of Justice account of the Booth-Smith escape, hijacking and move to Algeria', 'https://www.govinfo.gov/content/pkg/GOVPUB-Y4_J89_1-PURL-LPS42543/pdf/GOVPUB-Y4_J89_1-PURL-LPS42543.pdf'],
            ['FBI fugitive list identifying Clinton Robert Smith Jr. and the air-piracy and kidnapping charges', 'https://documents3.theblackvault.com/documents/jfkfiles/NARA-Oct2017/NARA-Dec15-2017/124-90146-10072.pdf'],
            ['Elaine Mokhtefi’s firsthand account of Cleaver’s confession and the discovery of Smith’s body', 'https://www.lrb.co.uk/the-paper/v39/n11/elaine-mokhtefi/diary'],
            ['The Nation review placing the reported killing in November 1969', 'https://www.thenation.com/article/archive/elaine-mokhtefi-algiers-book-review/'],
        ],
        'death_date' => [1969, 11],
        'case' => [
            'charges' => 'January 1969 escape from the California Institution for Men; federal air-piracy and kidnapping charges for the hijacking of National Airlines Flight 64.',
            'sentence' => 'Escaped while serving five years for second-degree robbery. Remained a fugitive after reaching Cuba and Algeria; later accounts place his death in Algiers in November 1969.',
            'in_exile_since' => [1969, 1, 28], 'end_of_exile' => [1969, 11],
        ],
    ],
    [
        'name' => 'Rosemary Woodruff Leary', 'first_name' => 'Rosemary', 'middle_name' => 'Sarah', 'last_name' => 'Woodruff Leary',
        'aka' => 'Rosemary Sarah Woodruff; Rosemary Leary; Sarah Woodruff; Ro',
        'birthdate' => [1935, 4, 26], 'death_date' => [2002, 2, 7], 'gender' => 'Female', 'race' => 'White', 'era' => '1960s',
        'affiliation' => ['Weather Underground'], 'ideologies' => ['Counterculture'], 'released' => true,
        'description' => 'Rosemary Woodruff Leary was a psychedelic activist who had already served a six-month California drug sentence when she worked with the Weather Underground to arrange Timothy Leary’s September 1970 prison escape. Using false papers, the couple fled the United States and received refuge with the Black Panther International Section in Algiers. After conflict with Eldridge Cleaver, they left Algeria for Switzerland in early 1971 and later separated. Woodruff remained a fugitive abroad in Europe, Asia and the Americas, secretly returned to the United States in 1976 and continued living as Sarah Woodruff. She surrendered in 1993; the outstanding bail-jumping and fugitive charges were dropped. She died in Aptos, California, on February 7, 2002.',
        'sources' => [
            ['New York Public Library guide to the Rosemary Woodruff Leary papers', 'https://archives.nypl.org/mss/23932'],
            ['Washington Post obituary and fugitive chronology', 'https://www.washingtonpost.com/archive/local/2002/02/11/rosemary-w-leary-66/ccb5f3d7-cb2c-4382-a483-e23a0c0d8d93/'],
            ['San Francisco Chronicle obituary with Woodruff’s full birth date', 'https://www.sfchronicle.com/news/article/Rosemary-Woodruff-LSD-guru-s-ex-wife-2876113.php'],
            ['The Nation on the Learys’ arrival and detention by the Panthers in Algiers', 'https://www.thenation.com/article/archive/elaine-mokhtefi-algiers-book-review/'],
        ],
        'case' => [
            'charges' => 'California drug case, bail jumping and fugitive charges; assisted Timothy Leary’s September 1970 prison escape.',
            'sentence' => 'Had served a six-month drug sentence before the escape. Lived abroad as a fugitive from 1970 until secretly returning in 1976; surrendered in 1993 and all remaining charges were dropped.',
            'in_exile_since' => [1970], 'end_of_exile' => [1976],
        ],
    ],
    [
        'name' => 'Barbara Easley-Cox', 'first_name' => 'Barbara', 'last_name' => 'Easley-Cox',
        'aka' => 'Barbara Cox Easley; Barbara Easley; Barbara Cox', 'gender' => 'Female', 'race' => 'Black', 'era' => '1960s',
        'affiliation' => ['Black Panther Party'], 'ideologies' => ['Black liberation'],
        'description' => 'Barbara Easley-Cox organized with the Black Panther Party in San Francisco, Oakland, Philadelphia and New York and worked in its breakfast, clothing and political-education programs. In 1970 she joined her husband, fugitive Panther field marshal Donald “DC” Cox, in Algiers and worked with the party’s International Section. She and Kathleen Cleaver traveled to North Korea for the births of their children, then returned to Algiers. Easley-Cox later organized with Black U.S. service members in Germany and returned to Philadelphia in 1973, where she continued community, antipoverty and youth-education work. Sources do not show a criminal charge against her; her record reflects political exile alongside her husband and her work in the International Section.',
        'sources' => [
            ['Binghamton University Libraries oral-history interview with Barbara Cox Easley', 'https://omeka.binghamton.edu/omeka/items/show/1191'],
            ['PBS POV interview on her Panther work and move to Algiers', 'https://archive.pov.org/apantherinafrica/interview-black-panthers-today/3/'],
            ['Los Angeles Times interview on Easley-Cox’s work from California to Algeria and North Korea', 'https://www.latimes.com/podcasts/story/2021-10-15/podcast-the-times-black-panthers-barbara-easley-cox'],
            ['CIA cable documenting Donald and Barbara Cox’s departure from Algiers in October 1972', 'https://www.archives.gov/files/research/jfk/releases/2023/104-10063-10176.pdf'],
        ],
        'case' => [
            'charges' => 'No personal criminal charge located; joined her husband Donald Cox in political exile and worked in the Black Panther International Section.',
            'sentence' => 'Lived and organized in Algeria, North Korea and Germany before returning to Philadelphia in 1973.',
            'in_exile_since' => [1970], 'end_of_exile' => [1973],
        ],
    ],
];

$newNames = array_column($newPeople, 'name');
$aliases = ['Byron Booth', 'Clinton R. Smith', 'Rahim Smith', 'Rosemary Leary', 'Rosemary Woodruff', 'Barbara Easley', 'Barbara Cox Easley'];
$duplicate = Prisoner::withoutGlobalScopes()->whereIn('name', array_merge($newNames, $aliases))->get(['id', 'name', 'aka']);
if ($duplicate->isNotEmpty()) {
    throw new RuntimeException('Identity guard: possible existing profiles: '.$duplicate->toJson(JSON_UNESCAPED_UNICODE));
}

$expectedExisting = [
    'Michael Tabor', 'Larry Mack', 'Sekou Odinga', 'Dhoruba bin Wahad', 'Connie Matthews', 'Timothy Leary',
];
$existing = Prisoner::withoutGlobalScopes()->whereIn('name', $expectedExisting)->with('cases')->get()->keyBy('name');
if ($existing->count() !== count($expectedExisting)) {
    throw new RuntimeException('Existing-profile guard failed: '.json_encode($existing->keys()->all()));
}

if (! $apply) {
    echo json_encode([
        'ready' => true,
        'profiles_to_create' => $newNames,
        'profiles_to_update' => $expectedExisting,
        'timothy_false_exile_case_id' => '83478772-176a-44fa-a5da-c97d6f1d40c6',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-algeria-exiles-followup-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$result = DB::transaction(function () use ($newPeople, $existing) {
    $created = [];
    foreach ($newPeople as $spec) {
        $caseSpec = $spec['case'];
        $sources = $spec['sources'];
        $birth = $spec['birthdate'] ?? null;
        $death = $spec['death_date'] ?? null;
        unset($spec['case'], $spec['sources'], $spec['birthdate'], $spec['death_date']);

        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false, 'released' => false, 'in_exile' => true,
            'currently_in_exile' => false, 'awaiting_trial' => false,
            'under_review' => false, 'body' => AlgeriaSources($sources),
        ], $spec));
        if ($birth) $profile->setPartialDate('birthdate', ...$birth);
        if ($death) $profile->setPartialDate('death_date', ...$death);
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
        foreach (['charges', 'sentence'] as $field) $case->{$field} = $caseSpec[$field];
        foreach (['in_exile_since', 'end_of_exile', 'arrest_date', 'sentenced_date', 'incarceration_date'] as $field) {
            if (! empty($caseSpec[$field])) $case->setPartialDate($field, ...$caseSpec[$field]);
        }
        $case->save();
        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }

    $tabor = $existing['Michael Tabor'];
    $tabor->in_exile = true;
    $tabor->currently_in_exile = false;
    $tabor->setPartialDate('death_date', 2010, 10, 17);
    $tabor->description = 'Michael Aloysius “Cetewayo” Tabor was a Harlem organizer, writer and member of the Black Panther Party’s New York leadership. Arrested in the April 2, 1969 Panther 21 raids, he spent about twenty-two months in pretrial custody. While free on bail during trial, Tabor fled to Algiers in February 1971 amid the Panther split and threats to his life. A jury acquitted all defendants, including Tabor in absentia, on May 13, 1971. He worked with the party’s International Section in Algeria, moved to Zambia with Connie Matthews in 1972 and never returned to the United States. He became a writer and radio broadcaster in Lusaka and died there on October 17, 2010.';
    $tabor->body = AlgeriaSources([
        ['New York Times obituary for Michael Tabor', 'https://www.nytimes.com/2010/10/24/nyregion/24tabor.html'],
        ['Jericho Movement memorial and exile account', 'https://www.thejerichomovement.com/node/300'],
        ['Cambridge Historical Journal study of the Panthers and Tabor’s flight to Algeria', 'https://www.cambridge.org/core/services/aop-cambridge-core/content/view/441702BF3381BFADB7EEEC0CB355D6E6/S0018246X18000201a.pdf/div-class-title-the-black-panther-party-s-publishing-strategies-and-the-financial-underpinnings-of-activism-1968-1975-div.pdf'],
        ['CIA cable documenting Tabor and Matthews leaving Algiers for East Africa in July 1972', 'https://www.archives.gov/files/research/jfk/releases/2023/104-10063-10176.pdf'],
    ]);
    $tabor->save();
    $taborCase = $tabor->cases->firstWhere('id', '858ad06a-ffdb-4a90-a246-2ee386ab85f0');
    $taborCase->sentence = 'Held about twenty-two months before release on bail. Fled to Algeria in February 1971; acquitted in absentia with the other Panther 21 defendants on May 13, 1971. Later settled in Zambia and remained abroad until his death on October 17, 2010.';
    $taborCase->setPartialDate('in_exile_since', 1971, 2);
    $taborCase->setPartialDate('end_of_exile', 2010, 10, 17);
    $taborCase->save();

    $mack = $existing['Larry Mack'];
    $mack->in_exile = true; $mack->currently_in_exile = false;
    $mack->description = 'Larry Mack was the section leader of the Queens branch of the Black Panther Party and a fugitive defendant in the Panther 21 conspiracy case. He avoided the April 2, 1969 raids and went underground. In late June 1970, Mack and Sekou Odinga traveled through Cuba to Algiers, where they received refuge and helped prepare the Black Panther Party’s International Section headquarters for its September opening. Mack remained with the Algiers group through at least April 1971. The surviving public sources reviewed for this pass do not establish when he left Algeria, whether he returned to the United States or his later life; the profile therefore records a historical exile without treating it as continuing today.';
    $mack->body = AlgeriaSources([
        ['The Nation on Mack, Odinga and the Panther International Section in Algiers', 'https://www.thenation.com/article/archive/elaine-mokhtefi-algiers-book-review/'],
        ['Don Cox memoir excerpt naming Mack in the Algiers headquarters work', 'https://www.thetedkarchive.com/library/don-cox-steve-wasserman-just-another-nigger'],
        ['April 3, 1971 International Section political-education transcript naming Mack', 'https://freedomarchives.org/Documents/Finder/DOC513_scans/BPP_Intercommunal/513.Black%20Panther%20%28New%29%20Intercommunal%20News%20Service.The_Black_Panther_1971.pdf'],
        ['June 14, 1969 Black Panther profile identifying Mack as a 25-year-old Queens organizer', 'https://blackfreedom.proquest.com/wp-content/uploads/2020/09/blackpanther17.pdf'],
    ]);
    $mack->save();
    $mackCase = $mack->cases->firstWhere('id', '568f7929-79c8-44ea-ae87-d0f06ec04213');
    $mackCase->sentence = 'Avoided the 1969 arrests, lived underground and reached Algeria through Cuba in late June 1970. Worked with the Black Panther International Section; later outcome not established by the sources reviewed.';
    $mackCase->setPartialDate('in_exile_since', 1970, 6);
    $mackCase->save();

    $sekou = $existing['Sekou Odinga'];
    $sekou->in_exile = true; $sekou->currently_in_exile = false;
    $sekou->aka = 'Nathanial Burns; Nathaniel Burns; Nathaniel Williams; Sekou Mgobogi Abdullah Odinga';
    $sekou->setPartialDate('birthdate', 1944, 6, 17);
    $sekou->description = 'Sekou Mgobogi Abdullah Odinga, born Nathanial Burns, helped found the Bronx chapter of the Black Panther Party and was named in the Panther 21 prosecution. He went underground in 1969, traveled through Cuba to Algiers in June 1970 and received political asylum from Algeria. There he helped establish the Black Panther Party’s International Section. Odinga later returned clandestinely to the United States—federal investigators estimated around 1973—and organized with the Black Liberation Army until his 1981 capture. He was released in 2014 after more than 33 years in state and federal custody and died on January 12, 2024.';
    $sekou->body = AlgeriaSources([
        ['Sekou Odinga’s own biography giving his June 17, 1944 birth date', 'https://freedomarchives.org/Documents/Finder/DOC3_scans/3.cant.jail.spirit.1985.pdf'],
        ['Elaine Mokhtefi’s firsthand description of Odinga as an exile in Algiers', 'https://www.lrb.co.uk/the-paper/v39/n11/elaine-mokhtefi/diary'],
        ['Akinyele Umoja, peer-reviewed history of Odinga’s asylum and estimated return', 'https://crossculturalsolidarity.com/wp-content/uploads/2021/02/Repression_breeds_resistance_The_Black_L-2copy.pdf'],
    ]);
    $sekou->save();
    $sekouExile = new PrisonerCase(['prisoner_id' => $sekou->id]);
    $sekouExile->charges = 'Panther 21 fugitive and Black Panther organizer; received political asylum in Algeria.';
    $sekouExile->sentence = 'Went underground in 1969, reached Algiers in June 1970 and returned clandestinely to the United States around 1973.';
    $sekouExile->setPartialDate('in_exile_since', 1970, 6);
    $sekouExile->setPartialDate('end_of_exile', 1973);
    $sekouExile->save();

    $dhoruba = $existing['Dhoruba bin Wahad'];
    $dhoruba->in_exile = true; $dhoruba->currently_in_exile = false;
    $dhoruba->aka = 'Richard Earl Moore; Richard Moore; Dhoruba Moore';
    $dhoruba->setPartialDate('birthdate', 1944, 6, 30);
    $dhoruba->description = 'Dhoruba al-Mujahid bin Wahad, born Richard Earl Moore on June 30, 1944, was a leader of the New York Black Panther Party and a Panther 21 defendant. While free on bail, he and Michael Tabor disappeared from the trial in February 1971 amid the Panther split and threats from within the organization. Contemporary and archival sources place Moore briefly in Algiers; a May 12 letter was identified as written there. He returned clandestinely to New York by June 1971. Later convicted of attempting to murder two police officers, he served nineteen years before a court vacated the conviction after suppressed COINTELPRO evidence came to light. He was released March 22, 1990.';
    $dhoruba->body = AlgeriaSources([
        ['Dhoruba bin Wahad case statement giving his birth date and legal history', 'https://freedomarchives.org/Documents/Finder/DOC513_scans/Dhoruba_Bin-Wahad/513.Dhoruba.statement.3.15.1990.pdf'],
        ['Cambridge Historical Journal study placing Moore and Tabor in Algeria', 'https://www.cambridge.org/core/services/aop-cambridge-core/content/view/441702BF3381BFADB7EEEC0CB355D6E6/S0018246X18000201a.pdf/div-class-title-the-black-panther-party-s-publishing-strategies-and-the-financial-underpinnings-of-activism-1968-1975-div.pdf'],
        ['Panther 21 sourcebook noting the May 12, 1971 letter written from Algeria', 'https://blackfreedom.proquest.com/wp-content/uploads/2020/09/blackpanther18.pdf'],
    ]);
    $dhoruba->save();
    $dhorubaExile = new PrisonerCase(['prisoner_id' => $dhoruba->id]);
    $dhorubaExile->charges = 'Jumped bail during the Panther 21 trial amid the 1971 Black Panther Party split.';
    $dhorubaExile->sentence = 'Briefly fled to Algeria in February 1971 and returned clandestinely to New York by June 1971; acquitted in absentia in the Panther 21 case on May 13, 1971.';
    $dhorubaExile->setPartialDate('in_exile_since', 1971, 2);
    $dhorubaExile->setPartialDate('end_of_exile', 1971, 6);
    $dhorubaExile->save();

    $connie = $existing['Connie Matthews'];
    $connie->body = AlgeriaSources([
        ['Cambridge Historical Journal study of Matthews, Tabor and Moore’s move to Algeria', 'https://www.cambridge.org/core/services/aop-cambridge-core/content/view/441702BF3381BFADB7EEEC0CB355D6E6/S0018246X18000201a.pdf/div-class-title-the-black-panther-party-s-publishing-strategies-and-the-financial-underpinnings-of-activism-1968-1975-div.pdf'],
        ['CIA cable documenting Matthews and Tabor leaving Algiers in July 1972', 'https://www.archives.gov/files/research/jfk/releases/2023/104-10063-10176.pdf'],
    ]);
    $connie->save();
    $connieCase = $connie->cases->firstWhere('id', 'ee92367e-5317-41b5-9c06-64fe9c3fbef2');
    $connieCase->setPartialDate('end_of_exile', 1993);
    $connieCase->sentence = 'No sentence. She left the United States with Panther 21 defendant Michael Tabor, joined the International Section in Algeria, moved to Zambia in 1972 and later returned to Jamaica, where she died in 1993.';
    $connieCase->save();

    $timothy = $existing['Timothy Leary'];
    $timothy->body = AlgeriaSources([
        ['Harvard Crimson report that Algeria granted Leary political asylum', 'https://www.thecrimson.com/article/1970/10/21/leary-is-in-algiers-ptimothy-leary/'],
        ['California appellate decision documenting Leary’s escape case and foreign custody', 'https://caselaw.findlaw.com/court/ca-court-of-appeal/1828111.html'],
        ['The Nation on Timothy and Rosemary Leary in Algiers', 'https://www.thenation.com/article/archive/elaine-mokhtefi-algiers-book-review/'],
    ]);
    $timothy->save();
    $falseExile = $timothy->cases->firstWhere('id', '83478772-176a-44fa-a5da-c97d6f1d40c6');
    if (! $falseExile || ! $falseExile->in_exile_since || $falseExile->in_exile_since->format('Y-m-d') !== '1976-04-21') {
        throw new RuntimeException('Timothy Leary derived-exile guard failed');
    }
    $precision = $falseExile->date_precision ?? [];
    unset($precision['in_exile_since'], $precision['end_of_exile']);
    DB::table('prisoner_cases')->where('id', $falseExile->id)->update([
        'in_exile_since' => null, 'end_of_exile' => null, 'in_exile_for_days' => null,
        'date_precision' => $precision ? json_encode($precision) : null, 'updated_at' => now(),
    ]);

    return ['created' => $created, 'updated' => $existing->keys()->values()->all(), 'timothy_false_exile_removed' => true];
});

Cache::forget(PrisonerApiController::cacheKey());
$verifyNames = array_merge($newNames, $existing->keys()->all());
$verify = Prisoner::withoutGlobalScopes()->whereIn('name', $verifyNames)->with('cases')->get()->keyBy('name');
$ok = $verify->count() === count($verifyNames)
    && collect($newNames)->every(fn ($name) => $verify[$name]->in_exile && ! $verify[$name]->currently_in_exile && ! $verify[$name]->under_review)
    && $verify['Michael Tabor']->cases->contains(fn ($case) => (bool) $case->in_exile_since)
    && $verify['Larry Mack']->cases->contains(fn ($case) => (bool) $case->in_exile_since)
    && $verify['Sekou Odinga']->birthdate?->format('Y-m-d') === '1944-06-17'
    && $verify['Dhoruba bin Wahad']->birthdate?->format('Y-m-d') === '1944-06-30'
    && ! $verify['Timothy Leary']->cases->firstWhere('id', '83478772-176a-44fa-a5da-c97d6f1d40c6')->in_exile_since;
if (! $ok) throw new RuntimeException('Post-publication verification failed');

echo json_encode(['backup' => $backup, 'result' => $result, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
