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

function must(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
}

$firstTrial = [
    ['Jorge Luis Jimenez', 'Jorge', 'Luis', 'Jimenez', 'Male', 72, 'a Chicago member of the Puerto Rican Nationalist Party'],
    ['Manuel Rabago Torres', 'Manuel', 'Rabago', 'Torres', 'Male', 72, 'a bodyguard to party president Pedro Albizu Campos who also attended meetings of the Chicago junta'],
    ['Juan Bernardo Lebron', 'Juan', 'Bernardo', 'Lebron', 'Male', 72, 'a member of the party’s New York junta'],
    ['Juan Francisco Ortiz Medina', 'Juan', 'Francisco Ortiz', 'Medina', 'Male', 72, 'president of the New York junta in 1953, whose home served as party headquarters'],
    ['Armando Diaz Matos', 'Armando', 'Diaz', 'Matos', 'Male', 72, 'treasurer and later one of the directors of the Chicago junta'],
    ['Carmelo Alvarez Roman', 'Carmelo', 'Alvarez', 'Roman', 'Male', 72, 'an officer of party juntas in New York and Puerto Rico'],
    ['Jose Antonio Otero Otero', 'Jose', 'Antonio Otero', 'Otero', 'Male', 72, 'vice-president of the New York junta, editor of its newspaper, and the party’s minister of propaganda'],
];

$secondTrial = [
    ['Juan Hernandez Valle', 'Juan', 'Hernandez', 'Valle', 'Male', 72],
    ['Maximino Pedraza Martinez', 'Maximino', 'Pedraza', 'Martinez', 'Male', 72],
    ['Esteban Quinones Escute', 'Esteban', 'Quinones', 'Escute', 'Male', 48],
    ['Angel Luis Arzola Velez', 'Angel', 'Luis Arzola', 'Velez', 'Male', 48],
    ['Antonio Herrera Moreno', 'Antonio', 'Herrera', 'Moreno', 'Male', 48],
    ['Carmen Dolores Otero de Torresola', 'Carmen', 'Dolores Otero', 'de Torresola', 'Female', 48],
    ['Pedro Aviles Vargas', 'Pedro', 'Aviles', 'Vargas', 'Male', 48],
    ['Julio Flores Medina', 'Julio', 'Flores', 'Medina', 'Male', 18],
    ['Miguel Vargas Nieves', 'Miguel', 'Vargas', 'Nieves', 'Male', 18],
];

$allNames = array_merge(array_column($firstTrial, 0), array_column($secondTrial, 0));
$duplicates = Prisoner::withoutGlobalScopes()->whereIn('name', $allNames)->get(['name', 'slug']);
must($duplicates->isEmpty(), 'One or more proposed profiles already exist: '.$duplicates->pluck('name')->join(', '));

$santiago = Prisoner::withoutGlobalScopes()->where('slug', 'santiago-gonzalez')->with('cases')->sole();
must($santiago->id === '74ed8265-76fe-41ca-8e99-77a9959c47fb', 'Santiago González identity changed');
must(! str_contains((string) $santiago->description, '1955'), 'Santiago second imprisonment already documented');

$mode = $argv[1] ?? 'check';
must(in_array($mode, ['check', 'apply'], true), 'Use check or apply.');
if ($mode === 'check') {
    echo json_encode(['ready' => true, 'new_profiles' => count($allNames), 'new_cases' => count($allNames) + 1, 'updated_profiles' => ['Santiago González Castro']], JSON_PRETTY_PRINT);
    exit;
}

$backup = storage_path('app/backups/before-flickr-puerto-rican-sedition-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$created = DB::transaction(function () use ($firstTrial, $secondTrial, $santiago): array {
    $created = [];
    foreach ($firstTrial as [$name, $first, $middle, $last, $gender, $months, $role]) {
        $p = Prisoner::create([
            'name' => $name, 'first_name' => $first, 'middle_name' => $middle, 'last_name' => $last,
            'gender' => $gender, 'era' => '1950s', 'state' => str_contains($role, 'Chicago') ? 'Illinois' : 'New York',
            'affiliation' => ['Puerto Rican Nationalist Party'], 'ideologies' => ['Puerto Rican Independence'],
            'in_custody' => false, 'released' => true, 'in_exile' => false, 'currently_in_exile' => false,
            'awaiting_trial' => false,
            'description' => "$name was $role. A federal grand jury indicted the party leaders on May 25, 1954 for seditious conspiracy, alleging a continuing campaign to end United States rule in Puerto Rico by force. A jury in the Southern District of New York convicted $name in October 1954. The court sentenced $name to six years in federal prison, and the Second Circuit affirmed the conviction on May 10, 1955.",
        ]);
        PrisonerCase::create([
            'prisoner_id' => $p->id, 'charges' => 'Seditious conspiracy under 18 U.S.C. § 2384',
            'sentenced_date' => '1954-01-01', 'date_precision' => ['sentenced_date' => 'year'],
            'convicted' => 'Yes — convicted in October 1954', 'sentence' => 'Six years in federal prison',
            'imprisoned_for_months' => $months,
        ]);
        $created[] = $p->name;
    }

    foreach ($secondTrial as [$name, $first, $middle, $last, $gender, $months]) {
        $years = $months === 18 ? '18 months' : ($months / 12).' years';
        $p = Prisoner::create([
            'name' => $name, 'first_name' => $first, 'middle_name' => $middle, 'last_name' => $last,
            'gender' => $gender, 'era' => '1950s', 'state' => 'New York',
            'affiliation' => ['Puerto Rican Nationalist Party'], 'ideologies' => ['Puerto Rican Independence'],
            'in_custody' => false, 'released' => true, 'in_exile' => false, 'currently_in_exile' => false,
            'awaiting_trial' => false,
            'description' => "$name was a member of the Puerto Rican Nationalist Party prosecuted in the second federal mass trial that followed the March 1954 attack on the United States House of Representatives. The government charged Nationalist organizers with seditious conspiracy as part of an alleged campaign to end United States rule in Puerto Rico by force. A jury convicted $name in 1955, and the court imposed a prison sentence of $years.",
        ]);
        PrisonerCase::create([
            'prisoner_id' => $p->id, 'charges' => 'Seditious conspiracy under 18 U.S.C. § 2384',
            'sentenced_date' => '1955-01-01', 'date_precision' => ['sentenced_date' => 'year'],
            'convicted' => 'Yes — convicted in 1955', 'sentence' => ucfirst($years).' in federal prison',
            'imprisoned_for_months' => $months,
        ]);
        $created[] = $p->name;
    }

    $santiago->name = 'Santiago González Castro';
    $santiago->last_name = 'González Castro';
    $santiago->description = rtrim((string) $santiago->description)."\n\nFederal authorities prosecuted González Castro again in 1955 with other Puerto Rican Nationalist Party members after the 1954 attack on Congress. Convicted of seditious conspiracy in the second New York mass trial, he received a six-year federal prison sentence.";
    $santiago->save();
    PrisonerCase::create([
        'prisoner_id' => $santiago->id, 'charges' => 'Seditious conspiracy under 18 U.S.C. § 2384',
        'sentenced_date' => '1955-01-01', 'date_precision' => ['sentenced_date' => 'year'],
        'convicted' => 'Yes — convicted in 1955', 'sentence' => 'Six years in federal prison',
        'imprisoned_for_months' => 72,
    ]);
    return $created;
});

Cache::forget(PrisonerApiController::cacheKey());
Cache::forget('museum:payload:v2');

must(Prisoner::withoutGlobalScopes()->whereIn('name', $created)->count() === 16, 'Created profile verification failed');
foreach ($created as $name) must(Prisoner::withoutGlobalScopes()->where('name', $name)->withCount('cases')->sole()->cases_count === 1, "Case verification failed: $name");
$santiago->refresh()->load('cases');
must($santiago->name === 'Santiago González Castro' && $santiago->cases->contains(fn ($case) => $case->imprisoned_for_months === 72), 'Santiago verification failed');

echo json_encode(['backup' => $backup, 'created' => $created, 'updated' => [$santiago->name]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
