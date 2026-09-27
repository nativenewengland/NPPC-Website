<?php

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function must2(bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); }

$groups = [
    ['date' => '1960-11-25', 'state' => 'Maryland', 'affiliation' => ['Congress of Racial Equality'], 'ideologies' => ['Civil Rights'], 'charges' => 'Trespassing during a sit-in at the segregated Colonial Restaurant', 'convicted' => null, 'sentence' => 'Arrested during the sit-in; the available account does not establish a conviction or sentence.', 'description' => 'was one of five Black Annapolis residents arrested on trespassing charges during a November 25, 1960 sit-in at the segregated Colonial Restaurant inside the city bus station. The protest won an agreement to serve African Americans on December 1.', 'people' => [
        ['William H. Johnson', 'Male'], ['Lacey McKinney', 'Male'], ['Ethel Mae Thompson', 'Female'], ['Mary M. Carroll', 'Female'], ['S. P. Callahan', 'Male'],
    ]],
    ['date' => '1969-06-21', 'state' => 'District of Columbia', 'affiliation' => ['Emergency Committee on the Transportation Crisis'], 'ideologies' => ['Civil Rights'], 'charges' => 'Unlawful entry during an anti-freeway housing protest', 'convicted' => null, 'sentence' => 'Arrested inside a home seized for freeway construction; the available account does not establish the disposition.', 'description' => 'was arrested for unlawful entry on June 21, 1969 after joining more than one hundred freeway opponents who entered and began repairing a Brookland home seized from Black families for highway construction.', 'people' => [
        ['Reginald Booker', 'Male'], ['Thomas Rooney', 'Male'], ['Thomas Coleman', 'Male'], ['John A. Mote', 'Male'],
    ]],
    ['date' => '1973-04-05', 'state' => 'District of Columbia', 'affiliation' => ['Gay Activists Alliance'], 'ideologies' => ['LGBTQ Rights'], 'charges' => 'Unlawful entry during a sit-in at the Metropolitan Police chief’s office', 'convicted' => null, 'sentence' => 'Arrested after remaining in the office at the end of normal business hours; the available account does not establish the disposition.', 'description' => 'was arrested on April 5, 1973 during a Gay Activists Alliance sit-in at District of Columbia police chief Jerry Wilson’s office. The group had sought a meeting for eighteen months to address police harassment of gay people.', 'people' => [
        ['Bill Bricker', 'Male'], ['Cade Ware', 'Male'], ['Lawrence “Deacon” McCubbin', 'Male'],
    ]],
];

$single = [
    ['Sammie Abbott', 'Male', '1969-06-21', 'District of Columbia', ['Emergency Committee on the Transportation Crisis'], ['Anti-Freeway Activism'], 'Disorderly conduct during an anti-freeway housing protest', 'was arrested for disorderly conduct on June 21, 1969 while trying to join four detained protesters in a police wagon. The five were opposing freeway-driven displacement in Brookland; Abbott was the Emergency Committee on the Transportation Crisis publicity director.'],
    ['George Wiley', 'Male', '1970-07-02', 'District of Columbia', ['National Welfare Rights Organization'], ['Welfare Rights'], 'Felony inciting to riot during a welfare-rights demonstration', 'was the director of the National Welfare Rights Organization when police arrested him and twelve others on July 2, 1970. Police used tear gas against about two hundred people demanding separate welfare grants for furniture and school clothes. Wiley was charged with felony inciting to riot; the available account does not establish the disposition.'],
    ['Debbie Danielle', 'Female', '1973-12-25', 'District of Columbia', ['Community for Creative Non-Violence'], ['Anti-War'], 'Entering the White House grounds during an antiwar protest', 'was arrested inside the White House grounds on December 25, 1973 after she and Mitch Snyder climbed the fence and tried to present President Richard Nixon with photographs of children maimed in Indochina. The action protested United States support for South Vietnamese violence.'],
    ['Elizabeth Murphy Oliver', 'Female', '1961-11-14', 'Maryland', ['Congress of Racial Equality', 'Afro-American newspaper'], ['Civil Rights'], 'Trespassing during an attempt to desegregate the Barnes Restaurant', 'was an Afro-American newspaper reporter arrested and held with nine other people on November 14, 1961 while covering and participating in a Congress of Racial Equality action at the segregated Barnes Restaurant in Annapolis.'],
    ['Laurence Henry', 'Male', '1960-06-10', 'Virginia', ['Nonviolent Action Group'], ['Civil Rights'], 'Trespassing during a sit-in at a segregated Howard Johnson’s restaurant', 'founded the Nonviolent Action Group at Howard University. Arlington police arrested him and Dion Diamond for trespassing on June 10, 1960 after they staged a sit-in at the segregated Howard Johnson’s restaurant on Lee Highway. The campaign helped desegregate Arlington lunch counters within weeks.'],
    ['Frederick C. Weaver', 'Male', '1934-03-17', 'District of Columbia', ['Washington Afro-American'], ['Civil Rights'], 'Arrested while attempting to arrange bail for a worker detained during the U.S. Capitol restaurant desegregation campaign', 'was a Howard University student, Washington Afro-American reporter, and organizer of the 1934 campaign against segregated public restaurants in the U.S. Capitol. Police arrested Weaver and three other students when they went to a precinct to arrange bail for waiter and fellow activist Harold Covington. The police captain dropped the charges and destroyed the arrest records.'],
    ['Hugo Gellert', 'Male', '1928-03-23', 'District of Columbia', ['Anti-Horthy League', 'Communist Party USA'], ['Anti-Fascism', 'Communism'], 'Picketing the White House without authorization', 'was a radical artist and leader of the Anti-Horthy League. Police arrested him on March 23, 1928 while he led a White House picket opposing President Calvin Coolidge’s reception of representatives of Hungary’s authoritarian Horthy government.'],
    ['Livia Gellert', 'Female', '1928-03-23', 'District of Columbia', ['Anti-Horthy League'], ['Anti-Fascism'], 'Picketing the White House without authorization', 'was an anti-fascist activist arrested on March 23, 1928 with her husband, artist Hugo Gellert, and other Anti-Horthy League picketers opposing a White House reception for representatives of Hungary’s Horthy government.'],
    ['Joseph Winkowsky', 'Male', '1930-01-04', 'District of Columbia', ['Young Communist League'], ['Anti-Imperialism', 'Communism'], 'Demonstrating without a permit at the Mexican Embassy', 'was a Young Communist League activist arrested with thirty-two other protesters at the Mexican Embassy on January 4, 1930. The demonstration opposed incoming Mexican president Pascual Ortiz Rubio’s closer relationship with the Hoover administration. A police officer knocked Winkowsky unconscious with a blow to the jaw; he was taken to a hospital while the others were taken to jail.'],
    ['Nanie Leah Washburn', 'Female', '1971-05-03', 'District of Columbia', ['Mayday Tribe'], ['Anti-War'], 'Mass arrest during the Mayday civil-disobedience campaign against the Vietnam War', 'was a seventy-one-year-old antiwar protester swept up in the May 3, 1971 Mayday mass arrests in Washington. She spent two days in the makeshift detention compound at the RFK Stadium practice field and described the confinement publicly after her release.'],
    ['Julius Hobson', 'Male', '1970-07-10', 'District of Columbia', ['D.C. Statehood Party'], ['Civil Rights', 'Socialism'], 'Refusing to pay a D.C. Transit fare increase as an organized protest', 'was a Washington civil-rights organizer and socialist arrested on July 10, 1970 for paying twenty-five cents of a new thirty-two-cent bus fare during an organized fare strike. He was arrested again on February 25, 1972 for paying twenty-five cents of a forty-cent fare; a court imposed three hours in jail, later suspended because he had spinal cancer.'],
    ['Dion Diamond', 'Male', '1960-06-10', 'Virginia', ['Nonviolent Action Group', 'Student Nonviolent Coordinating Committee'], ['Civil Rights'], 'Trespassing during a sit-in at a segregated Howard Johnson’s restaurant', 'was a student civil-rights organizer arrested with Laurence Henry on June 10, 1960 for a sit-in at the segregated Howard Johnson’s restaurant on Lee Highway in Arlington. It was the first of roughly thirty arrests Diamond later recalled from protests against segregation and voting-rights violations.'],
];

$names = [];
foreach ($groups as $group) foreach ($group['people'] as [$name]) $names[] = $name;
foreach ($single as [$name]) $names[] = $name;
$dupes = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->get(['name']);
must2($dupes->isEmpty(), 'Profiles already exist: '.$dupes->pluck('name')->join(', '));

$mode = $argv[1] ?? 'check';
must2(in_array($mode, ['check', 'apply'], true), 'Use check or apply.');
if ($mode === 'check') { echo json_encode(['ready' => true, 'new_profiles' => count($names), 'new_cases' => count($names) + 1], JSON_PRETTY_PRINT); exit; }

$backup = storage_path('app/backups/before-flickr-direct-action-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));
$created = DB::transaction(function () use ($groups, $single): array {
    $created = [];
    $make = function (array $p, array $case) use (&$created): Prisoner {
        $parts = preg_split('/\s+/', str_replace(['“', '”'], '', $p['name']));
        $profile = Prisoner::create([
            'name' => $p['name'], 'first_name' => $parts[0], 'last_name' => end($parts), 'gender' => $p['gender'],
            'era' => substr($case['arrest_date'], 0, 3).'0s', 'state' => $p['state'], 'affiliation' => $p['affiliation'],
            'ideologies' => $p['ideologies'], 'description' => $p['name'].' '.$p['description'],
            'in_custody' => false, 'released' => true, 'in_exile' => false, 'currently_in_exile' => false,
            'awaiting_trial' => false, 'minor_case' => true,
        ]);
        PrisonerCase::create(array_merge(['prisoner_id' => $profile->id, 'date_precision' => ['arrest_date' => 'day']], $case));
        $created[] = $profile->name;
        return $profile;
    };
    foreach ($groups as $g) foreach ($g['people'] as [$name, $gender]) $make([
        'name' => $name, 'gender' => $gender, 'state' => $g['state'], 'affiliation' => $g['affiliation'],
        'ideologies' => $g['ideologies'], 'description' => $g['description'],
    ], ['arrest_date' => $g['date'], 'charges' => $g['charges'], 'convicted' => $g['convicted'], 'sentence' => $g['sentence']]);
    foreach ($single as [$name, $gender, $date, $state, $affiliation, $ideologies, $charges, $description]) {
        $case = ['arrest_date' => $date, 'charges' => $charges, 'sentence' => 'The available sources document the arrest but do not establish a custodial sentence or final disposition.'];
        if ($name === 'Nanie Leah Washburn') { $case['sentence'] = 'Held for two days in the RFK Stadium detention compound.'; $case['imprisoned_for_days'] = 2; }
        $p = $make(compact('name', 'gender', 'state', 'affiliation', 'ideologies', 'description'), $case);
        if ($name === 'Julius Hobson') PrisonerCase::create([
            'prisoner_id' => $p->id, 'arrest_date' => '1972-02-25', 'date_precision' => ['arrest_date' => 'day'],
            'charges' => 'Refusing to pay the full D.C. Transit fare as a protest', 'convicted' => 'Yes',
            'sentence' => 'Three hours in jail, suspended after Hobson was diagnosed with spinal cancer.',
        ]);
    }
    return $created;
});

Cache::forget(PrisonerApiController::cacheKey()); Cache::forget('museum:payload:v2');
must2(Prisoner::withoutGlobalScopes()->whereIn('name', $created)->count() === count($created), 'Profile verification failed');
foreach ($created as $name) must2(Prisoner::withoutGlobalScopes()->where('name', $name)->withCount('cases')->sole()->cases_count >= 1, "Case verification failed: $name");
echo json_encode(['backup' => $backup, 'created' => $created, 'profiles' => count($created), 'cases' => count($created) + 1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
