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

function must5(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
}

$selmaBio = 'joined the first recorded sit-in inside the White House on March 11, 1965, demanding federal protection for voting-rights marchers after Bloody Sunday in Selma. After refusing repeated orders to leave, the demonstrators were arrested at about 6 p.m. and convicted of unlawful entry; the D.C. Court of Appeals affirmed the convictions in 1966.';
$selma = [
    ['David H. Whittlesey', 'Male'],
    ['Pamela C. Haynes', 'Female'],
    ['Marta C. Kusic', 'Female'],
    ['Carol J. Lawson', 'Female'],
    ['Jessie W. McQueen', 'Female'],
    ['Sheila P. Ryan', 'Female'],
    ['Robert E. Wooten', 'Male'],
];

$rows = [];
foreach ($selma as [$name, $gender]) {
    $rows[] = [
        'name' => $name, 'gender' => $gender, 'date' => '1965-03-11', 'era' => '1960s',
        'state' => 'District of Columbia', 'affiliation' => [], 'ideologies' => ['Civil Rights', 'Voting Rights'],
        'description' => $name.' '.$selmaBio, 'charges' => 'Unlawful entry at the White House',
        'sentence' => 'Convicted of unlawful entry; the available appellate opinion does not state the sentence.',
        'convicted' => 'Yes — conviction affirmed on appeal', 'release_date' => null,
    ];
}

$rows = array_merge($rows, [
    [
        'name' => 'J. Holmes Smith', 'gender' => 'Male', 'date' => '1943-02-22', 'era' => '1940s',
        'state' => 'District of Columbia', 'affiliation' => ['Harlem Ashram'],
        'ideologies' => ['Indian Independence', 'Pacifism', 'Anti-Imperialism'],
        'description' => 'J. Holmes Smith was a former Methodist missionary in India and a cofounder of the interracial Harlem Ashram. Police arrested him outside the British Embassy on February 22, 1943 while he picketed for the release of Mahatma Gandhi and Jawaharlal Nehru. Smith and Ralph Templin announced that they would conduct a hunger strike in jail.',
        'charges' => 'Picketing within 500 feet of an embassy', 'sentence' => 'The documented arrest included detention in jail; no completed sentence is established.', 'convicted' => null, 'release_date' => null,
    ],
    [
        'name' => 'Ralph T. Templin', 'gender' => 'Male', 'date' => '1943-02-22', 'era' => '1940s',
        'state' => 'District of Columbia', 'affiliation' => ['Harlem Ashram'],
        'ideologies' => ['Indian Independence', 'Pacifism', 'Anti-Imperialism'],
        'description' => 'Ralph T. Templin was a former Methodist missionary in India and a cofounder of the interracial Harlem Ashram. Police arrested him outside the British Embassy on February 22, 1943 while he picketed for the release of Mahatma Gandhi and Jawaharlal Nehru. Templin and J. Holmes Smith announced that they would conduct a hunger strike in jail.',
        'charges' => 'Picketing within 500 feet of an embassy', 'sentence' => 'The documented arrest included detention in jail; no completed sentence is established.', 'convicted' => null, 'release_date' => null,
    ],
    [
        'name' => 'Marjorie Kendrick', 'gender' => 'Female', 'date' => '1943-07-05', 'era' => '1940s',
        'state' => 'District of Columbia', 'affiliation' => [],
        'ideologies' => ['Indian Independence', 'Anti-Imperialism'],
        'description' => 'Marjorie Kendrick was arrested outside the British Embassy on July 5, 1943 while picketing for the release of Mahatma Gandhi and Jawaharlal Nehru and supporting the Quit India movement.',
        'charges' => 'Picketing at the British Embassy', 'sentence' => 'The available account documents the arrest but does not establish a custodial sentence.', 'convicted' => null, 'release_date' => null,
    ],
    [
        'name' => 'Jane Fulton', 'gender' => 'Female', 'date' => '1943-07-05', 'era' => '1940s',
        'state' => 'District of Columbia', 'affiliation' => [],
        'ideologies' => ['Indian Independence', 'Anti-Imperialism'],
        'description' => 'Jane Fulton of Pittsburgh was arrested outside the British Embassy on July 5, 1943 while picketing for the release of Mahatma Gandhi and Jawaharlal Nehru and supporting the Quit India movement.',
        'charges' => 'Picketing at the British Embassy', 'sentence' => 'The available account documents the arrest but does not establish a custodial sentence.', 'convicted' => null, 'release_date' => null,
    ],
    [
        'name' => 'Harold R. Lefever', 'gender' => 'Male', 'date' => '1943-07-05', 'era' => '1940s',
        'state' => 'District of Columbia', 'affiliation' => [],
        'ideologies' => ['Indian Independence', 'Anti-Imperialism'],
        'description' => 'Harold R. Lefever of York, Pennsylvania was arrested outside the British Embassy on July 5, 1943 while picketing for the release of Mahatma Gandhi and Jawaharlal Nehru and supporting the Quit India movement.',
        'charges' => 'Picketing at the British Embassy', 'sentence' => 'The available account documents the arrest but does not establish a custodial sentence.', 'convicted' => null, 'release_date' => null,
    ],
    [
        'name' => 'Leonard P. Matlovich', 'gender' => 'Male', 'date' => '1987-06-01', 'era' => '1980s',
        'state' => 'District of Columbia', 'affiliation' => [],
        'ideologies' => ['LGBTQ Rights', 'HIV/AIDS Activism'],
        'description' => 'Leonard P. Matlovich was a decorated Air Force veteran and gay-rights activist. Police arrested him on June 1, 1987 after he joined a roadway sit-in outside the White House protesting the Reagan administration’s response to AIDS. He was released several hours later after paying a $50 fine.',
        'charges' => 'Disorderly conduct for blocking Pennsylvania Avenue', 'sentence' => '$50 fine; released several hours after arrest.', 'convicted' => 'Yes — paid fine', 'release_date' => '1987-06-01',
    ],
    [
        'name' => 'Patrick B. “Paddy” Whalen', 'gender' => 'Male', 'date' => '1938-02-06', 'era' => '1930s',
        'state' => 'New Jersey', 'affiliation' => ['National Maritime Union'],
        'ideologies' => ['Labor', 'Anti-Racism'],
        'description' => 'Patrick B. “Paddy” Whalen led the Baltimore branch of the National Maritime Union and fought for integrated crews and hiring halls. New Jersey police arrested him and four companions on February 6, 1938 while they were traveling to a union executive committee meeting. They were charged with carrying concealed and dangerous weapons amid union members’ suspicions that the arrest was politically motivated. Authorities later dropped the charges.',
        'charges' => 'Carrying concealed and dangerous weapons', 'sentence' => 'No sentence; charges were dropped.', 'convicted' => 'No — charges dropped', 'release_date' => null,
    ],
]);

$names = array_column($rows, 'name');
$dupes = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->get(['name']);
must5($dupes->isEmpty(), 'Profiles already exist: '.$dupes->pluck('name')->join(', '));
$mode = $argv[1] ?? 'check';
must5(in_array($mode, ['check', 'apply'], true), 'Use check or apply.');
if ($mode === 'check') { echo json_encode(['ready'=>true,'new_profiles'=>count($rows),'new_cases'=>count($rows)], JSON_PRETTY_PRINT); exit; }

$backup = storage_path('app/backups/before-flickr-civil-disobedience-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));
$created = DB::transaction(function () use ($rows): array {
    $created = [];
    foreach ($rows as $row) {
        $parts = preg_split('/\s+/', str_replace(['“','”'], '', $row['name']));
        $prisoner = Prisoner::create([
            'name'=>$row['name'], 'first_name'=>$parts[0], 'last_name'=>end($parts), 'gender'=>$row['gender'],
            'era'=>$row['era'], 'state'=>$row['state'], 'affiliation'=>$row['affiliation'], 'ideologies'=>$row['ideologies'],
            'description'=>$row['description'], 'in_custody'=>false, 'released'=>true, 'in_exile'=>false,
            'currently_in_exile'=>false, 'awaiting_trial'=>false, 'minor_case'=>true,
        ]);
        $case = [
            'prisoner_id'=>$prisoner->id, 'arrest_date'=>$row['date'], 'date_precision'=>['arrest_date'=>'day'],
            'charges'=>$row['charges'], 'sentence'=>$row['sentence'], 'convicted'=>$row['convicted'],
        ];
        if ($row['release_date']) { $case['release_date']=$row['release_date']; $case['date_precision']['release_date']='day'; }
        PrisonerCase::create($case);
        $created[] = $row['name'];
    }
    return $created;
});
Cache::forget(PrisonerApiController::cacheKey()); Cache::forget('museum:payload:v2');
must5(Prisoner::withoutGlobalScopes()->whereIn('name',$created)->count()===count($created),'Profile verification failed');
foreach($created as $name) must5(Prisoner::withoutGlobalScopes()->where('name',$name)->withCount('cases')->sole()->cases_count===1,"Case verification failed: $name");
echo json_encode(['backup'=>$backup,'created'=>$created],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
