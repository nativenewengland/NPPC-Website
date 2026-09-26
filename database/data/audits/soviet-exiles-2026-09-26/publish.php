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

function sourceBody(array $sources): string
{
    $items = array_map(
        fn (array $source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    );

    return '<h2>Sources</h2><ul>'.implode('', $items).'</ul>';
}

$intrepidSources = [
    ['TIME, “The War: Caviar & Encomiums,” December 1, 1967', 'https://time.com/archive/6647901/the-war-caviar-encomiums/'],
    ['Carl-Gustaf Scott, study of American war resisters in Sweden', 'https://www.diva-portal.org/smash/get/diva2%3A1079025/FULLTEXT02.pdf'],
];

$people = [
    [
        'name' => 'Craig W. Anderson', 'first_name' => 'Craig', 'middle_name' => 'W.', 'last_name' => 'Anderson',
        'gender' => 'Male', 'state' => 'California', 'era' => '1960s',
        'ideologies' => ['Anti-War', 'Vietnam War Resistance'], 'affiliation' => ['Intrepid Four', 'United States Navy'],
        'description' => 'Craig W. Anderson was one of the Intrepid Four, four enlisted U.S. Navy sailors who refused to return to the aircraft carrier USS Intrepid during shore leave in Japan on October 23, 1967. Anderson, then 20 and from San Jose, California, joined John Barilla, Richard Bailey and Michael Lindner in declaring that their desertion was a protest against the Vietnam War. Japanese antiwar organizers hid the men and moved them aboard the Soviet liner Baikal on November 11. They spent about a month in the Soviet Union, where they made public antiwar statements, and reached Sweden in December 1967. Anderson’s Soviet stay was a transit stage in a longer exile in Sweden.',
        'sources' => $intrepidSources,
        'case' => ['charges' => 'Desertion from the USS Intrepid after refusing to return from shore leave in Japan as a protest against the Vietnam War.', 'in_exile_since' => [1967, 10, 23], 'end_of_exile' => [1967, 12], 'sentence' => 'Remained outside U.S. military jurisdiction; traveled through the Soviet Union and entered exile in Sweden.'],
    ],
    [
        'name' => 'John Barilla', 'first_name' => 'John', 'last_name' => 'Barilla',
        'gender' => 'Male', 'state' => 'Maryland', 'era' => '1960s',
        'ideologies' => ['Anti-War', 'Vietnam War Resistance'], 'affiliation' => ['Intrepid Four', 'United States Navy'],
        'description' => 'John Barilla was one of the Intrepid Four, four enlisted U.S. Navy sailors who refused to return to the aircraft carrier USS Intrepid during shore leave in Japan on October 23, 1967. Barilla, then 20 and from Catonsville, Maryland, joined Craig Anderson, Richard Bailey and Michael Lindner in presenting the desertion as an act of opposition to the Vietnam War. Japanese antiwar organizers hid the men and moved them aboard the Soviet liner Baikal on November 11. They spent about a month in the Soviet Union, appeared publicly to explain their opposition to the war, and reached Sweden in December 1967. The Soviet Union was a stop on Barilla’s route to Swedish exile.',
        'sources' => $intrepidSources,
        'case' => ['charges' => 'Desertion from the USS Intrepid after refusing to return from shore leave in Japan as a protest against the Vietnam War.', 'in_exile_since' => [1967, 10, 23], 'end_of_exile' => [1967, 12], 'sentence' => 'Remained outside U.S. military jurisdiction; traveled through the Soviet Union and entered exile in Sweden.'],
    ],
    [
        'name' => 'Richard D. Bailey', 'first_name' => 'Richard', 'middle_name' => 'D.', 'last_name' => 'Bailey',
        'gender' => 'Male', 'state' => 'Florida', 'era' => '1960s',
        'ideologies' => ['Anti-War', 'Vietnam War Resistance'], 'affiliation' => ['Intrepid Four', 'United States Navy'],
        'description' => 'Richard D. Bailey was one of the Intrepid Four, four enlisted U.S. Navy sailors who deserted the aircraft carrier USS Intrepid in Japan on October 23, 1967. Bailey, then 19 and from Jacksonville, Florida, said that assisting the carrier’s air operations had made him feel that he was participating in murder. Japanese antiwar organizers concealed Bailey, Craig Anderson, John Barilla and Michael Lindner and moved them aboard the Soviet liner Baikal on November 11. The four spent about a month in the Soviet Union making antiwar statements before reaching Sweden in December 1967. Bailey’s Soviet stay was transit within a longer exile from U.S. military authority.',
        'sources' => $intrepidSources,
        'case' => ['charges' => 'Desertion from the USS Intrepid after refusing to return from shore leave in Japan as a protest against the Vietnam War.', 'in_exile_since' => [1967, 10, 23], 'end_of_exile' => [1967, 12], 'sentence' => 'Remained outside U.S. military jurisdiction; traveled through the Soviet Union and entered exile in Sweden.'],
    ],
    [
        'name' => 'Michael A. Lindner', 'first_name' => 'Michael', 'middle_name' => 'A.', 'last_name' => 'Lindner', 'aka' => 'Michael Sutherland; Mike Sutherland',
        'gender' => 'Male', 'state' => 'Pennsylvania', 'era' => '1960s',
        'ideologies' => ['Anti-War', 'Vietnam War Resistance'], 'affiliation' => ['Intrepid Four', 'United States Navy'],
        'description' => 'Michael A. Lindner, who later used the name Michael Sutherland, was one of the Intrepid Four. The four enlisted U.S. Navy sailors refused to return to the aircraft carrier USS Intrepid during shore leave in Japan on October 23, 1967 in opposition to the Vietnam War. Lindner was 19 and from Mount Pocono, Pennsylvania. Japanese antiwar organizers hid the men and moved them aboard the Soviet liner Baikal on November 11. They spent about a month in the Soviet Union, where they publicly described their antiwar motives, before reaching Sweden in December 1967. Lindner later wrote that seeing the scale of the carrier’s bombing operations had changed how he understood the war.',
        'sources' => $intrepidSources,
        'case' => ['charges' => 'Desertion from the USS Intrepid after refusing to return from shore leave in Japan as a protest against the Vietnam War.', 'in_exile_since' => [1967, 10, 23], 'end_of_exile' => [1967, 12], 'sentence' => 'Remained outside U.S. military jurisdiction; traveled through the Soviet Union and entered exile in Sweden.'],
    ],
    [
        'name' => 'Maurice Hyman Halperin', 'first_name' => 'Maurice', 'middle_name' => 'Hyman', 'last_name' => 'Halperin',
        'gender' => 'Male', 'birthdate' => [1906, 3, 3], 'death_date' => [1995, 2, 9], 'state' => 'Massachusetts', 'era' => '1950s',
        'ideologies' => ['Communism'], 'affiliation' => ['Office of Strategic Services', 'Boston University'],
        'description' => 'Maurice Hyman Halperin was a scholar, former State Department and Office of Strategic Services official, and Boston University professor. In 1953 the Senate Internal Security Subcommittee questioned him about allegations that he had supplied information to Soviet intelligence. Halperin denied the accusations, invoked the Fifth Amendment, lost his university position and moved to Mexico. With extradition a concern, he and his wife moved to the Soviet Union in December 1955, where he worked with the Soviet Academy of Sciences. He left for Cuba in 1962 and later settled in Canada. Later archival scholarship treated the espionage allegations as substantiated, but no U.S. criminal trial adjudicated them before his flight.',
        'sources' => [
            ['Don S. Kirschner, Cold War Exile: The Unclosed Case of Maurice Halperin', 'https://books.google.com/books?id=fywJycUadCEC'],
            ['Declassified U.S. record concerning Halperin’s Soviet residence', 'https://www.archives.gov/files/research/jfk/releases/2022/104-10172-10111.pdf'],
        ],
        'case' => ['charges' => 'Accused in Senate proceedings of assisting Soviet intelligence; the allegation was denied and was not resolved in a U.S. criminal trial before he left the country.', 'in_exile_since' => [1955, 12], 'end_of_exile' => [1962], 'sentence' => 'Left Mexico for the Soviet Union amid possible extradition; later moved from the USSR to Cuba.'],
    ],
    [
        'name' => 'Wade E. Roberts', 'first_name' => 'Wade', 'middle_name' => 'E.', 'last_name' => 'Roberts',
        'gender' => 'Male', 'birthdate' => [1965], 'state' => 'California', 'era' => '1980s',
        'ideologies' => ['Military Resistance'], 'affiliation' => ['United States Army'],
        'description' => 'Wade E. Roberts was a U.S. Army private stationed in West Germany who went absent without leave on March 2, 1987. Roberts and his West German partner reached East Germany, and the Soviet Union granted them political asylum. Soviet authorities settled them in Ashkhabad, in the Turkmen Soviet republic. Roberts said he had sought asylum after harassment in the Army, although the record located does not tie his action to opposition to a particular war. He returned to West Germany on November 3, 1987 to face military proceedings. A court-martial later cleared him of desertion and convicted him of the lesser AWOL offense.',
        'sources' => [
            ['Los Angeles Times, April 9, 1987', 'https://www.latimes.com/archives/la-xpm-1987-04-09-mn-399-story.html'],
            ['Washington Post, November 5, 1987', 'https://www.washingtonpost.com/archive/politics/1987/11/05/us-soldier-leaves-moscow-to-face-charges-in-west/229f9ea3-abd2-463b-a831-a7bc6b17ba66/'],
            ['UPI, February 28, 1988', 'https://www.upi.com/Archives/1988/02/28/Soldier-cleared-of-desertion-has-no-regrets/8254573022800/'],
        ],
        'case' => ['charges' => 'Absence without leave from a U.S. Army unit in West Germany; initially listed as a deserter.', 'arrest_date' => [1987, 3, 2], 'in_exile_since' => [1987, 3, 2], 'end_of_exile' => [1987, 11, 3], 'convicted' => 'Acquitted of desertion; convicted of absence without leave after his return.', 'sentence' => 'Returned voluntarily from the Soviet Union to West Germany to face military proceedings.'],
    ],
    [
        'name' => 'Arnold Lockshin', 'first_name' => 'Arnold', 'last_name' => 'Lockshin',
        'gender' => 'Male', 'birthdate' => [1939, 2, 3], 'state' => 'Texas', 'era' => '1980s',
        'ideologies' => ['Communism', 'Anti-Militarism'],
        'description' => 'Arnold Lockshin was a Houston cancer researcher and Communist activist who moved with his wife and three children to Moscow in October 1986 after receiving Soviet political asylum. Lockshin said that the FBI had harassed his family and that his dismissal from a research laboratory was political retaliation for his Communist organizing and opposition to U.S. defense and foreign policy. His former employer said the dismissal concerned his research performance, and contemporary U.S. reporting did not substantiate all of his allegations. Lockshin remained in Russia and received Russian citizenship in 1992. This record attributes his persecution account to him rather than treating the disputed claim as a judicial finding.',
        'sources' => [
            ['Ronald Reagan Presidential Library news summary, October 9, 1986', 'https://www.reaganlibrary.gov/sites/default/files/2025-03/40-399-5730359-391-004-2025.pdf'],
            ['Study of Soviet and Russian political-asylum practice', 'https://refugee.ru/wp-content/uploads/2022/07/troitsky-on-political-asylum.pdf'],
            ['UPI citizenship follow-up, August 9, 1992', 'https://www.upi.com/amp/Archives/1992/08/09/American-defector-wins-Russian-citizenship/5451713332800/'],
        ],
        'case' => ['charges' => 'No U.S. criminal charge located; Lockshin alleged political surveillance, threats and employment retaliation.', 'in_exile_since' => [1986, 10, 8], 'sentence' => 'Granted political asylum by the Soviet Union; later received Russian citizenship.'],
    ],
    [
        'name' => 'Morris Cohen', 'first_name' => 'Morris', 'last_name' => 'Cohen', 'aka' => 'Peter Kroger',
        'gender' => 'Male', 'birthdate' => [1910, 7, 2], 'death_date' => [1995, 6, 23], 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'], 'affiliation' => ['Communist Party USA'],
        'description' => 'Morris Cohen was an American Communist, veteran of the Abraham Lincoln Brigade and Soviet intelligence agent. As the Rosenberg investigation reached members of the same network in 1950, Cohen and his wife Lona left New York through Mexico and eventually reached the Soviet bloc. Under the identities Peter and Helen Kroger, they operated an illegal radio and document relay for the Soviet network in Britain. British authorities arrested them in January 1961, and a court sentenced each to twenty years under the Official Secrets Act. They were released in an October 1969 spy exchange and sent to the Soviet Union, where Morris Cohen remained until his death.',
        'sources' => [
            ['FBI, Morris and Lona Cohen file', 'https://vault.fbi.gov/Morris%20and%20Linda%20Cohen/Morris%20and%20Lona%20Cohen%20Part%2001/view'],
            ['U.S. National Counterintelligence history', 'https://www.odni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['Science Museum account of the Krogers’ transmitter', 'https://blog.sciencemuseum.org.uk/the-krogers-radio-transmitter/'],
        ],
        'case' => ['charges' => 'British prosecution under the Official Secrets Act for operating as an illegal Soviet intelligence courier and radio link.', 'arrest_date' => [1961, 1, 7], 'incarceration_date' => [1961, 3], 'release_date' => [1969, 10, 24], 'in_exile_since' => [1950], 'end_of_exile' => [1995, 6, 23], 'convicted' => 'Yes — convicted in Britain in March 1961.', 'sentence' => 'Twenty years in prison; released in a 1969 exchange and sent to the Soviet Union.'],
    ],
    [
        'name' => 'Lona Cohen', 'first_name' => 'Lona', 'last_name' => 'Cohen', 'aka' => 'Helen Kroger; Leontina Cohen',
        'gender' => 'Female', 'birthdate' => [1913, 1, 11], 'death_date' => [1992, 12, 23], 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'], 'affiliation' => ['Communist Party USA'],
        'description' => 'Lona Cohen was an American Communist and Soviet intelligence courier. As the Rosenberg investigation reached the network in 1950, she and her husband Morris left New York through Mexico and eventually reached the Soviet bloc. Under the identities Helen and Peter Kroger, the Cohens operated an illegal radio and document relay for a Soviet intelligence network in Britain. British authorities arrested them in January 1961, and a court sentenced each to twenty years under the Official Secrets Act. They were released in an October 1969 spy exchange and sent to the Soviet Union, where Lona Cohen remained until her death.',
        'sources' => [
            ['FBI, Morris and Lona Cohen file', 'https://vault.fbi.gov/Morris%20and%20Linda%20Cohen/Morris%20and%20Lona%20Cohen%20Part%2001/view'],
            ['U.S. National Counterintelligence history', 'https://www.odni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['Science Museum account of the Krogers’ transmitter', 'https://blog.sciencemuseum.org.uk/the-krogers-radio-transmitter/'],
        ],
        'case' => ['charges' => 'British prosecution under the Official Secrets Act for operating as an illegal Soviet intelligence courier and radio link.', 'arrest_date' => [1961, 1, 7], 'incarceration_date' => [1961, 3], 'release_date' => [1969, 10, 24], 'in_exile_since' => [1950], 'end_of_exile' => [1992, 12, 23], 'convicted' => 'Yes — convicted in Britain in March 1961.', 'sentence' => 'Twenty years in prison; released in a 1969 exchange and sent to the Soviet Union.'],
    ],
    [
        'name' => 'Joel Barr', 'first_name' => 'Joel', 'last_name' => 'Barr', 'aka' => 'Joseph Berg; Joe Berg',
        'gender' => 'Male', 'birthdate' => [1916], 'death_date' => [1998], 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'],
        'description' => 'Joel Barr was an American electrical engineer associated with Julius Rosenberg’s wartime intelligence network. Barr was working in Europe when David Greenglass was arrested in 1950. Fearing that the investigation would reach him, he fled Paris for Czechoslovakia and later moved to the Soviet Union under the name Joseph Berg. Declassified Venona material identified him with Soviet intelligence activity; Barr denied spying and said that his close political and personal association with Rosenberg drove his flight. In the Soviet Union he became an important engineer in the development of the country’s microelectronics industry.',
        'sources' => [
            ['U.S. National Counterintelligence history, “Joel Barr and Al Sarant”', 'https://www.odni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['PBS/NOVA Venona document and commentary', 'https://www.pbs.org/wgbh/nova/venona/inte_19441114.html'],
        ],
        'case' => ['charges' => 'Suspected participation in the Rosenberg-era Soviet intelligence network; he left Europe before any U.S. criminal trial.', 'in_exile_since' => [1950], 'end_of_exile' => [1998], 'sentence' => 'Fled from Paris to Czechoslovakia after David Greenglass’s arrest and later settled in the Soviet Union.'],
    ],
    [
        'name' => 'Alfred Sarant', 'first_name' => 'Alfred', 'last_name' => 'Sarant', 'aka' => 'Philip Georgievich Staros; Filipp Staros',
        'gender' => 'Male', 'birthdate' => [1918, 9, 26], 'death_date' => [1979, 3, 12], 'state' => 'New York', 'era' => '1950s',
        'ideologies' => ['Communism'],
        'description' => 'Alfred Sarant was an American electrical engineer linked by decrypted Venona messages to Julius Rosenberg’s Soviet intelligence network. The FBI questioned him after Rosenberg’s arrest in July 1950. Sarant then fled through Mexico with Carol Dayton, reached Czechoslovakia, and moved to the Soviet Union after about five years. Using the name Philip Staros, he became a prominent engineer in the Soviet electronics industry. He was never tried in the United States; the archival identification and his flight are recorded separately from a criminal conviction.',
        'sources' => [
            ['U.S. National Counterintelligence history, “Joel Barr and Al Sarant”', 'https://www.odni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['FBI Alfred Sarant file', 'https://vault.fbi.gov/rosenberg-case/alfred-sarant/Alfred%20Sarant%20Part%2081/at_download/file'],
        ],
        'case' => ['charges' => 'Suspected participation in the Rosenberg-era Soviet intelligence network; questioned by the FBI but not tried before leaving the United States.', 'in_exile_since' => [1950, 7], 'end_of_exile' => [1979, 3, 12], 'sentence' => 'Fled through Mexico to Czechoslovakia and later settled in the Soviet Union.'],
    ],
    [
        'name' => 'Edward Lee Howard', 'first_name' => 'Edward', 'middle_name' => 'Lee', 'last_name' => 'Howard',
        'gender' => 'Male', 'birthdate' => [1951, 10, 27], 'death_date' => [2002], 'state' => 'New Mexico', 'era' => '1980s',
        'ideologies' => [], 'affiliation' => ['Central Intelligence Agency'],
        'description' => 'Edward Lee Howard was a former CIA officer accused of providing classified information to the Soviet Union. After a Soviet defector identified him in August 1985, the FBI placed Howard under surveillance in Santa Fe. In September he escaped using a homemade dummy and help from his wife, crossed the country and ultimately reached the Soviet Union. The FBI wanted him for espionage and interstate flight, but he was never tried in the United States. Soviet authorities publicly granted him political asylum in 1986. He remained in Russia until his death in 2002.',
        'sources' => [
            ['FBI history of Edward Lee Howard’s escape', 'https://www.fbi.gov/history/artifacts/edward-lee-howard-dummy'],
            ['Washington Post asylum report, August 8, 1986', 'https://www.washingtonpost.com/archive/politics/1986/08/08/soviets-grant-asylum-to-fugitive-cia-agent/5e975d03-346f-4a18-b6f5-1207beffaf65/'],
        ],
        'case' => ['charges' => 'Wanted by the FBI for espionage and interstate flight following a probation violation; never tried in the United States.', 'in_exile_since' => [1985, 9], 'end_of_exile' => [2002], 'sentence' => 'Escaped FBI surveillance and received Soviet political asylum in 1986.'],
    ],
    [
        'name' => 'Glenn Michael Souther', 'first_name' => 'Glenn', 'middle_name' => 'Michael', 'last_name' => 'Souther', 'aka' => 'Mikhail Yevgenyevich Orlov',
        'gender' => 'Male', 'birthdate' => [1957, 1, 30], 'death_date' => [1989, 6], 'state' => 'Virginia', 'era' => '1980s',
        'ideologies' => [], 'affiliation' => ['United States Navy'],
        'description' => 'Glenn Michael Souther was a former U.S. Navy photographer and intelligence specialist with access to sensitive reconnaissance material. A joint Naval Investigative Service and FBI inquiry examined whether he had passed classified information to the Soviet Union. After investigators alerted him to their suspicions, Souther disappeared in May 1986 and reached the Soviet Union. Soviet authorities announced in 1988 that they had granted him asylum and citizenship under the name Mikhail Yevgenyevich Orlov. Soviet reporting after his death in June 1989 acknowledged his intelligence work.',
        'sources' => [
            ['Washington Post, “Ex-Sailor Defects to Soviets,” July 18, 1988', 'https://www.washingtonpost.com/archive/politics/1988/07/18/ex-sailor-defects-to-soviets/026db635-f855-4087-8dec-333f2d89b5a4/'],
            ['Sandia National Laboratories counterintelligence case study', 'https://www.osti.gov/servlets/purl/1762656'],
        ],
        'case' => ['charges' => 'Subject of a joint FBI and Naval Investigative Service espionage inquiry; no U.S. trial occurred before his flight.', 'in_exile_since' => [1986, 5], 'end_of_exile' => [1989, 6], 'sentence' => 'Left during the inquiry and later received Soviet asylum and citizenship.'],
    ],
    [
        'name' => 'William Hamilton Martin', 'first_name' => 'William', 'middle_name' => 'Hamilton', 'last_name' => 'Martin',
        'gender' => 'Male', 'birthdate' => [1931], 'death_date' => [1987], 'state' => 'Washington', 'era' => '1960s',
        'ideologies' => ['Anti-Militarism', 'Whistleblowing'], 'affiliation' => ['National Security Agency'],
        'description' => 'William Hamilton Martin was a National Security Agency cryptologist who defected with his colleague Bernon Mitchell in 1960. The two traveled to Mexico and Cuba before appearing in Moscow in September. At a Soviet press conference they denounced U.S. electronic surveillance of allies and neutral countries and criticized American reconnaissance flights and military incursions. The United States later indicted them for disclosing classified information, but no pending prosecution has been found before their departure. Martin received Soviet citizenship, became dissatisfied with life there, and moved to Mexico in the 1960s.',
        'sources' => [
            ['U.S. National Counterintelligence history', 'https://www.dni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['Contemporary newspaper account, September 6, 1960', 'https://oregonnews.uoregon.edu/lccn/sn90051770/1960-09-06/ed-1/seq-1.pdf'],
        ],
        'case' => ['charges' => 'Later indicted for disclosing classified National Security Agency information; no pending charge located before departure.', 'in_exile_since' => [1960, 6], 'end_of_exile' => [1963], 'sentence' => 'Defected through Mexico and Cuba and publicly announced the defection in Moscow; later left the Soviet Union for Mexico.'],
    ],
    [
        'name' => 'Bernon F. Mitchell', 'first_name' => 'Bernon', 'middle_name' => 'F.', 'last_name' => 'Mitchell',
        'gender' => 'Male', 'birthdate' => [1929], 'death_date' => [2001], 'state' => 'California', 'era' => '1960s',
        'ideologies' => ['Anti-Militarism', 'Whistleblowing'], 'affiliation' => ['National Security Agency'],
        'description' => 'Bernon F. Mitchell was a National Security Agency cryptologist who defected with William Hamilton Martin in 1960. The two traveled to Mexico and Cuba before appearing in Moscow in September. They used a Soviet press conference to denounce U.S. electronic surveillance of allies and neutral countries and to criticize reconnaissance flights and military incursions. The United States later indicted them for disclosing classified information, but no pending prosecution has been found before their departure. Mitchell received Soviet citizenship and remained in the Soviet Union and Russia for the rest of his life.',
        'sources' => [
            ['U.S. National Counterintelligence history', 'https://www.dni.gov/files/NCSC/documents/ci/CI_Reader_Vol3.pdf'],
            ['Contemporary newspaper account, September 6, 1960', 'https://oregonnews.uoregon.edu/lccn/sn90051770/1960-09-06/ed-1/seq-1.pdf'],
        ],
        'case' => ['charges' => 'Later indicted for disclosing classified National Security Agency information; no pending charge located before departure.', 'in_exile_since' => [1960, 6], 'end_of_exile' => [2001], 'sentence' => 'Defected through Mexico and Cuba, publicly announced the defection in Moscow, and remained in the Soviet Union and Russia.'],
    ],
];

$arnoldSpec = collect($people)->firstWhere('name', 'Arnold Lockshin');
$people = array_values(array_filter($people, fn (array $person) => $person['name'] !== 'Arnold Lockshin'));

$newNames = array_column($people, 'name');
$duplicates = Prisoner::withUnderReview()->whereIn('name', $newNames)->pluck('name');
if ($duplicates->isNotEmpty()) {
    throw new RuntimeException('Identity guard: profiles already exist: '.$duplicates->implode(', '));
}

$existingIds = [
    'Bill Haywood' => '1df3e50a-6f5f-45d3-b93d-def5b96a29b1',
    'George Andreytchine' => '53d027f4-dd25-4514-a20a-2793315d98cf',
    'Vladimir Lossieff' => 'd8f998e4-fceb-4743-a644-cfb5358225bb',
    'J. H. Beyer' => '48a7c549-4dc4-46fc-af5d-919f3273c8f5',
    'Herbert McCutcheon' => '4951db16-c610-4c29-9104-ef4d4d7f5a19',
    'Grover H. Perry' => 'e3d5b762-a3d1-4d20-bbc9-0ba404c7c716',
    'Charles Rothfiser' => '735cdbc5-9007-42fb-a00a-89a4c94d4aea',
    'Leo Laukki' => '0a127cdb-f9d6-4fe1-9ebf-a97d4c5a9b13',
    'Fred Jaakkola' => 'fc8c1474-57fe-4085-8823-05cda2a8467e',
    'Fred Beal' => '6806c52a-c354-442d-b69b-ad4a3d244117',
    'Clarence Miller' => '33412d02-e48e-4417-b14b-2c1ced311b71',
    'George Carter' => '889799d9-a1ce-426e-a25a-f65379a180cd',
    'Joseph Harrison' => 'c7368220-ccec-435a-9742-6353f1d7c1ba',
    'W. M. McGinnis' => '3917c3f6-66f6-415a-a21c-e5d8f867cf4f',
    'Louis McLaughlin' => '6746793c-4f50-4cd1-8253-87b355f6a93e',
    'K. Y. Hendricks' => '3930d15d-026f-4efe-b9fe-26effae8e303',
    'Eugene Dennis' => 'e88088dd-3996-4b81-9bb2-3162e0420b30',
    'Arnold Lockshin' => '982032dc-f66a-4dfc-9cd6-b429f2523002',
];
foreach ($existingIds as $name => $id) {
    $record = Prisoner::withoutGlobalScopes()->findOrFail($id);
    if ($record->name !== $name) {
        throw new RuntimeException("Identity guard failed for {$name}");
    }
}

$existingCases = [
    'Bill Haywood Idaho' => ['ab353c59-49d8-4c94-9cde-0f8c5b25f896', '1df3e50a-6f5f-45d3-b93d-def5b96a29b1'],
    'George Andreytchine' => ['cc998280-c2ce-4626-8995-e88d91b26190', '53d027f4-dd25-4514-a20a-2793315d98cf'],
    'Vladimir Lossieff' => ['6e1554a9-542b-4515-8dd9-6374221eb5af', 'd8f998e4-fceb-4743-a644-cfb5358225bb'],
    'J. H. Beyer' => ['6001d5d9-8a81-4cde-8e1f-0dc627cd653a', '48a7c549-4dc4-46fc-af5d-919f3273c8f5'],
    'Herbert McCutcheon' => ['3a72530c-ae92-45c5-958b-0a38fd7028b3', '4951db16-c610-4c29-9104-ef4d4d7f5a19'],
    'Grover H. Perry' => ['e9cdba6f-3da0-4478-ac53-d51cb8c20ef5', 'e3d5b762-a3d1-4d20-bbc9-0ba404c7c716'],
    'Charles Rothfiser' => ['045b6aa3-1490-41a0-a9a3-a5c00e4c405a', '735cdbc5-9007-42fb-a00a-89a4c94d4aea'],
    'Leo Laukki' => ['47cdf348-f452-4eb6-9bc2-4cee13f40383', '0a127cdb-f9d6-4fe1-9ebf-a97d4c5a9b13'],
    'Fred Jaakkola' => ['c21d324c-e817-4dfb-b545-bdf2409492e6', 'fc8c1474-57fe-4085-8823-05cda2a8467e'],
    'Fred Beal' => ['e97465b6-ce2c-486f-9c01-fcc52b5a6496', '6806c52a-c354-442d-b69b-ad4a3d244117'],
    'Clarence Miller' => ['0b83df4c-a701-45e2-b795-aaba75f6f608', '33412d02-e48e-4417-b14b-2c1ced311b71'],
    'George Carter' => ['9b7cb80c-2811-46f7-af8d-fc96b8206eb1', '889799d9-a1ce-426e-a25a-f65379a180cd'],
    'Joseph Harrison' => ['feb0e4b6-08f5-49e0-863f-d31d97fc0e0e', 'c7368220-ccec-435a-9742-6353f1d7c1ba'],
    'W. M. McGinnis' => ['3c1bd38a-6202-4575-b964-00b765f8b982', '3917c3f6-66f6-415a-a21c-e5d8f867cf4f'],
    'Louis McLaughlin' => ['4e3d05ad-cd1d-4577-a5bd-5e773352a7f1', '6746793c-4f50-4cd1-8253-87b355f6a93e'],
    'K. Y. Hendricks' => ['71fd6b00-4782-4901-b1b3-54abc95800bb', '3930d15d-026f-4efe-b9fe-26effae8e303'],
    'Arnold Lockshin' => ['d2c15e7a-2509-4d00-9409-c1c7b17e6bfd', '982032dc-f66a-4dfc-9cd6-b429f2523002'],
];
foreach ($existingCases as $label => [$caseId, $ownerId]) {
    $case = PrisonerCase::query()->findOrFail($caseId);
    if ($case->prisoner_id !== $ownerId) {
        throw new RuntimeException("Case owner guard failed for {$label}");
    }
}

if (! $apply) {
    echo json_encode([
        'ready' => true,
        'profiles_to_create' => count($people),
        'existing_profiles_to_correct' => count($existingIds),
        'new_names' => $newNames,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-soviet-exiles-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$created = DB::transaction(function () use ($people, $arnoldSpec, $existingIds, $existingCases) {
    $created = [];

    foreach ($people as $spec) {
        $caseSpec = $spec['case'];
        $sources = $spec['sources'];
        $birthdate = $spec['birthdate'] ?? null;
        $deathDate = $spec['death_date'] ?? null;
        unset($spec['case'], $spec['sources'], $spec['birthdate'], $spec['death_date']);

        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false,
            'released' => false,
            'in_exile' => true,
            'currently_in_exile' => false,
            'awaiting_trial' => false,
            'under_review' => false,
            'body' => sourceBody($sources),
        ], $spec));
        if ($birthdate) $profile->setPartialDate('birthdate', ...$birthdate);
        if ($deathDate) $profile->setPartialDate('death_date', ...$deathDate);
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
        foreach (['charges', 'indicted', 'convicted', 'plead', 'sentence'] as $field) {
            if (array_key_exists($field, $caseSpec)) $case->{$field} = $caseSpec[$field];
        }
        foreach (['arrest_date', 'sentenced_date', 'incarceration_date', 'release_date', 'in_exile_since', 'end_of_exile'] as $field) {
            if (! empty($caseSpec[$field])) $case->setPartialDate($field, ...$caseSpec[$field]);
        }
        $case->save();

        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'id' => $profile->id, 'slug' => $profile->slug, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }

    $iwwSources = sourceBody([
        ['Industrial Pioneer, June 1921, report naming the nine bail jumpers', 'https://www.marxists.org/history/usa/pubs/industrial-pioneer/Industrial%20Pioneer%20%28June%201921%29_0.pdf'],
        ['Industrial Workers of the World chronology', 'https://archive.iww.org/about/chronology/4/'],
    ]);
    foreach (['George Andreytchine', 'Vladimir Lossieff', 'J. H. Beyer', 'Herbert McCutcheon', 'Grover H. Perry', 'Charles Rothfiser', 'Leo Laukki', 'Fred Jaakkola'] as $name) {
        $profile = Prisoner::withoutGlobalScopes()->findOrFail($existingIds[$name]);
        $profile->in_exile = true;
        $profile->currently_in_exile = false;
        $profile->body = trim(($profile->body ?? '').$iwwSources);
        $profile->save();
        $case = PrisonerCase::query()->findOrFail($existingCases[$name][0]);
        $case->setPartialDate('in_exile_since', 1921);
        $case->save();
    }

    DB::table('prisoner_cases')->where('id', $existingCases['Bill Haywood Idaho'][0])->update([
        'in_exile_since' => null,
        'in_exile_for_days' => null,
        'date_precision' => json_encode(['arrest_date' => 'day', 'incarceration_date' => 'day', 'release_date' => 'day']),
        'updated_at' => now(),
    ]);

    $arnold = Prisoner::withoutGlobalScopes()->findOrFail($existingIds['Arnold Lockshin']);
    $arnold->description = $arnoldSpec['description'];
    $arnold->ideologies = $arnoldSpec['ideologies'];
    $arnold->in_exile = true;
    $arnold->currently_in_exile = false;
    $arnold->years_in_prison = null;
    $arnold->body = sourceBody($arnoldSpec['sources']);
    $arnold->save();
    $arnoldCase = PrisonerCase::query()->findOrFail($existingCases['Arnold Lockshin'][0]);
    $arnoldCase->charges = $arnoldSpec['case']['charges'];
    $arnoldCase->sentence = $arnoldSpec['case']['sentence'];
    $arnoldCase->setPartialDate('in_exile_since', ...$arnoldSpec['case']['in_exile_since']);
    $arnoldCase->save();

    $gastoniaSources = sourceBody([
        ['North Carolina Supreme Court opinion, State v. Beal', 'https://app.midpage.ai/document/state-v-beal-3672480'],
        ['Harvard Law School finding aid for the Gastonia trial transcripts', 'https://hollisarchives.lib.harvard.edu/catalog/law00163'],
        ['Labor Defender, February 1931, letter from Moscow', 'https://marxists.architexturez.net/history/usa/pubs/labordefender/1931/v06n02-feb-1931-LD.pdf'],
        ['Associated Press report, September 20, 1930', 'https://cdn.manchesterhistory.org/News/Manchester%20Evening%20Hearld_1930-09-20.pdf'],
        ['Study recording Louis McLaughlin’s later presence in Moscow', 'https://files.eric.ed.gov/fulltext/ED261450.pdf'],
    ]);
    $gastonia = [
        'Fred Beal' => ['sentence' => 'Seventeen to twenty years in prison. Jumped bail pending appeal; returned to the United States in 1938, surrendered, and later served part of the sentence.', 'exile' => true, 'end' => [1938], 'bio' => 'Fred Beal was a National Textile Workers Union organizer convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. Beal received a sentence of seventeen to twenty years. He and six codefendants jumped bail while their appeals were pending. Beal reached the Soviet Union in 1930, became disillusioned, returned to the United States, and surrendered in 1938. He served part of the North Carolina sentence before receiving parole in 1942.'],
        'Clarence Miller' => ['sentence' => 'Seventeen to twenty years in prison. Jumped bail pending appeal and was in Moscow by December 1930.', 'exile' => true, 'bio' => 'Clarence Miller was a National Textile Workers Union organizer convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. Miller received a sentence of seventeen to twenty years and jumped bail while his appeal was pending. A December 30, 1930 letter published from Moscow places Miller there with Fred Beal, Joseph Harrison and K. Y. Hendricks. The available evidence does not establish the end of Miller’s Soviet exile.'],
        'George Carter' => ['sentence' => 'Seventeen to twenty years in prison. Jumped bail pending appeal; a September 1930 report placed him in hiding in the United States.', 'exile' => false, 'bio' => 'George Carter was one of seven National Textile Workers Union defendants convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. Carter received a sentence of seventeen to twenty years and jumped bail while his appeal was pending. Although earlier versions of this profile said that all seven defendants fled to the Soviet Union, a September 1930 report placed Carter in hiding in the United States, and the Moscow evidence located for the group does not name him.'],
        'Joseph Harrison' => ['sentence' => 'Seventeen to twenty years in prison. Jumped bail pending appeal and was in Moscow by December 1930.', 'exile' => true, 'bio' => 'Joseph Harrison was a National Textile Workers Union organizer convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. Harrison received a sentence of seventeen to twenty years and jumped bail while his appeal was pending. A December 30, 1930 letter published from Moscow places Harrison there with Fred Beal, Clarence Miller and K. Y. Hendricks. The available evidence does not establish the end of Harrison’s Soviet exile.'],
        'W. M. McGinnis' => ['sentence' => 'Twelve to fifteen years in prison. Jumped bail pending appeal; no reliable evidence located places him in the Soviet Union.', 'exile' => false, 'bio' => 'W. M. McGinnis was a Loray Mill striker and one of seven defendants convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the strikers’ tent colony. McGinnis received a sentence of twelve to fifteen years and jumped bail while his appeal was pending. Earlier text incorrectly replaced him with Robert Allen in the list of convicted defendants and said that every one of the seven fled to the Soviet Union. The sources located identify McGinnis as one of the seven but do not place him in Moscow.'],
        'Louis McLaughlin' => ['sentence' => 'Twelve to fifteen years in prison. Jumped bail pending appeal; was still hiding in the United States in September 1930 and was later reported in Moscow.', 'exile' => true, 'bio' => 'Louis McLaughlin was one of seven National Textile Workers Union defendants convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. McLaughlin received a sentence of twelve to fifteen years and jumped bail while his appeal was pending. A September 1930 report still placed him in hiding in the United States; later historical research records him in Moscow. His departure therefore should not be dated earlier than the group’s 1930 bail flight, and the available evidence does not establish when his Soviet exile ended.'],
        'K. Y. Hendricks' => ['sentence' => 'Five to seven years in prison. Jumped bail pending appeal and was in Moscow by December 1930.', 'exile' => true, 'bio' => 'K. Y. Hendricks was a National Textile Workers Union defendant convicted on October 21, 1929 of conspiracy in the death of Gastonia police chief Orville F. Aderholt during the June 7 police raid on the Loray Mill strikers’ tent colony. Hendricks received a sentence of five to seven years and jumped bail while his appeal was pending. A December 30, 1930 letter published from Moscow places Hendricks there with Fred Beal, Clarence Miller and Joseph Harrison. The available evidence does not establish the end of Hendricks’s Soviet exile.'],
    ];
    foreach ($gastonia as $name => $spec) {
        $profile = Prisoner::withoutGlobalScopes()->findOrFail($existingIds[$name]);
        $profile->description = $spec['bio'];
        $profile->in_exile = $spec['exile'];
        $profile->currently_in_exile = false;
        $profile->body = trim(($profile->body ?? '').$gastoniaSources);
        $profile->save();
        $case = PrisonerCase::query()->findOrFail($existingCases[$name][0]);
        $case->sentence = $spec['sentence'];
        if ($spec['exile']) {
            $case->setPartialDate('in_exile_since', 1930);
            if (! empty($spec['end'])) $case->setPartialDate('end_of_exile', ...$spec['end']);
        } else {
            $case->in_exile_since = null;
            $case->end_of_exile = null;
            $precision = $case->date_precision ?? [];
            unset($precision['in_exile_since'], $precision['end_of_exile']);
            $case->date_precision = $precision;
        }
        $case->save();
    }

    $dennis = Prisoner::withoutGlobalScopes()->findOrFail($existingIds['Eugene Dennis']);
    $dennis->aka = 'Francis Xavier Waldron; Tim Ryan; Paul Walsh';
    $dennis->in_exile = true;
    $dennis->currently_in_exile = false;
    $dennis->description = 'Eugene Dennis, born Francis Xavier Waldron and also known as Tim Ryan and Paul Walsh, was a Communist Party USA organizer and later its general secretary. After a California criminal-syndicalism prosecution in 1930, he went underground rather than serve the resulting jail sentence and left for the Soviet Union under a false passport around January 1931. He returned to the United States in 1935 as Eugene Dennis. In 1947 he refused to appear before the House Un-American Activities Committee and later served a one-year contempt sentence. He was also a lead defendant in the 1949 Foley Square Smith Act trial and served a five-year federal sentence after the Supreme Court affirmed the convictions.';
    $dennis->body = trim(($dennis->body ?? '').sourceBody([
        ['Harvey Klehr and John Earl Haynes, account of Dennis’s early prosecution and Soviet years', 'https://marxism.suda.edu.cn/_upload/article/files/f9/2d/1fd4dd2b4c08b6cd8a9b5063f3e0/ba466ce1-9cd0-47b3-b825-c9ab68c3c98b.pdf'],
        ['Biographical chronology of Eugene Dennis', 'https://prabook.com/web/eugene.dennis/3767582'],
    ]));
    $dennis->save();
    $dennisCase = new PrisonerCase(['prisoner_id' => $dennis->id]);
    $dennisCase->charges = 'California criminal-syndicalism prosecution arising from Communist Party organizing.';
    $dennisCase->convicted = 'Yes — he went underground rather than serve the jail sentence.';
    $dennisCase->sentence = 'Jail sentence not served before flight; left under a false passport and lived in the Soviet Union until returning in 1935.';
    $dennisCase->setPartialDate('arrest_date', 1930);
    $dennisCase->setPartialDate('in_exile_since', 1931, 1);
    $dennisCase->setPartialDate('end_of_exile', 1935);
    $dennisCase->save();

    return $created;
});

Cache::forget(PrisonerApiController::cacheKey());

$verification = [
    'created_profiles' => Prisoner::withoutGlobalScopes()->whereIn('name', $newNames)->count(),
    'historical_exiles_created' => Prisoner::withoutGlobalScopes()->whereIn('name', $newNames)->where('in_exile', true)->count(),
    'haywood_idaho_exile_removed' => DB::table('prisoner_cases')->where('id', $existingCases['Bill Haywood Idaho'][0])->value('in_exile_since') === null,
    'carter_exile_removed' => ! Prisoner::withoutGlobalScopes()->findOrFail($existingIds['George Carter'])->in_exile,
    'mcginnis_exile_removed' => ! Prisoner::withoutGlobalScopes()->findOrFail($existingIds['W. M. McGinnis'])->in_exile,
    'dennis_early_case_added' => PrisonerCase::query()->where('prisoner_id', $existingIds['Eugene Dennis'])->whereYear('in_exile_since', 1931)->exists(),
];
if ($verification['created_profiles'] !== count($people)
    || $verification['historical_exiles_created'] !== count($people)
    || in_array(false, $verification, true)) {
    throw new RuntimeException('Post-publication verification failed: '.json_encode($verification));
}

echo json_encode([
    'backup' => $backup,
    'created' => $created,
    'existing_profiles_corrected' => array_keys($existingIds),
    'verification' => $verification,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
