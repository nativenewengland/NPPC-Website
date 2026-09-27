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

function must4(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$rows = [
    [
        'name' => 'Marianne W. Fowler',
        'gender' => 'Female',
        'era' => '1970s',
        'state' => 'Virginia',
        'affiliation' => ['National Organization for Women', 'Virginia ERA-PAC'],
        'ideologies' => ['Feminism', 'Equal Rights'],
        'description' => 'Marianne W. Fowler was a Virginia organizer for the Equal Rights Amendment. Capitol police arrested her on February 9, 1978 after a Virginia House committee voted to block the amendment and she joined a protest in the Capitol. She was charged with disorderly conduct, trespassing, and assault on an officer.',
        'arrest_date' => '1978-02-09',
        'charges' => 'Disorderly conduct, trespassing, and assault on a police officer',
        'sentence' => 'The available accounts document an arrest during the protest but do not establish a custodial sentence.',
        'convicted' => null,
        'release_date' => '1978-02-09',
        'release_precision' => 'day',
    ],
    [
        'name' => 'Jean Marshall Clarke',
        'gender' => 'Female',
        'era' => '1970s',
        'state' => 'Virginia',
        'affiliation' => ['National Organization for Women'],
        'ideologies' => ['Feminism', 'Equal Rights'],
        'description' => 'Jean Marshall Clarke was the Virginia coordinator of the National Organization for Women. Capitol police arrested her on February 9, 1978 during a protest after a Virginia House committee voted to block the Equal Rights Amendment. Witnesses reported that she went limp and was dragged from the Capitol grounds.',
        'arrest_date' => '1978-02-09',
        'charges' => 'Civil disturbance at a public building',
        'sentence' => 'The available accounts document an arrest during the protest but do not establish a custodial sentence.',
        'convicted' => null,
        'release_date' => '1978-02-09',
        'release_precision' => 'day',
    ],
    [
        'name' => 'Rose Smith',
        'gender' => 'Female',
        'era' => '1960s',
        'state' => 'Connecticut',
        'affiliation' => ['Black Panther Party'],
        'ideologies' => ['Black Liberation', 'Socialism'],
        'description' => 'Rose Smith was a sixteen-year-old member of the New Haven Black Panther Party when police arrested her in May 1969 in the prosecution that became known as the New Haven Panther trials. Held without bail at the Connecticut State Prison for Women in Niantic, she gave birth while incarcerated and reported severe mistreatment. After roughly sixteen months in custody, Smith pleaded guilty to aggravated assault under an agreement that produced a suspended sentence and her release.',
        'arrest_date' => '1969-05-22',
        'charges' => 'Kidnapping, conspiracy to kidnap, conspiracy to murder, and related charges; resolved by a plea to aggravated assault',
        'sentence' => 'Suspended sentence after a guilty plea to aggravated assault; approximately sixteen months had already been spent jailed without bail.',
        'convicted' => 'Yes — guilty plea to reduced charge',
        'release_date' => '1970-09',
        'release_precision' => 'month',
    ],
];

$names = array_column($rows, 'name');
$dupes = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->get(['name']);
must4($dupes->isEmpty(), 'Profiles already exist: '.$dupes->pluck('name')->join(', '));

$mode = $argv[1] ?? 'check';
must4(in_array($mode, ['check', 'apply'], true), 'Use check or apply.');
if ($mode === 'check') {
    echo json_encode(['ready' => true, 'new_profiles' => count($rows), 'new_cases' => count($rows)], JSON_PRETTY_PRINT);
    exit;
}

$backup = storage_path('app/backups/before-flickr-era-panther-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$created = DB::transaction(function () use ($rows): array {
    $created = [];
    foreach ($rows as $row) {
        $parts = preg_split('/\s+/', $row['name']);
        $prisoner = Prisoner::create([
            'name' => $row['name'],
            'first_name' => $parts[0],
            'last_name' => end($parts),
            'gender' => $row['gender'],
            'era' => $row['era'],
            'state' => $row['state'],
            'affiliation' => $row['affiliation'],
            'ideologies' => $row['ideologies'],
            'description' => $row['description'],
            'in_custody' => false,
            'released' => true,
            'in_exile' => false,
            'currently_in_exile' => false,
            'awaiting_trial' => false,
            'minor_case' => true,
        ]);
        $case = [
            'prisoner_id' => $prisoner->id,
            'arrest_date' => $row['arrest_date'],
            'date_precision' => ['arrest_date' => 'day'],
            'charges' => $row['charges'],
            'sentence' => $row['sentence'],
            'convicted' => $row['convicted'],
            'release_date' => $row['release_date'],
        ];
        $case['date_precision']['release_date'] = $row['release_precision'];
        PrisonerCase::create($case);
        $created[] = $row['name'];
    }
    return $created;
});

Cache::forget(PrisonerApiController::cacheKey());
Cache::forget('museum:payload:v2');
must4(Prisoner::withoutGlobalScopes()->whereIn('name', $created)->count() === count($created), 'Profile verification failed');
foreach ($created as $name) {
    must4(Prisoner::withoutGlobalScopes()->where('name', $name)->withCount('cases')->sole()->cases_count === 1, "Case verification failed: $name");
}

echo json_encode(['backup' => $backup, 'created' => $created], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
