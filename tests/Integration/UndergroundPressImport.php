<?php

// Run with: php tests/Integration/UndergroundPressImport.php
// Only a fresh in-memory SQLite connection is used; no application data is read.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

foreach (array_keys(config('database.connections')) as $connection) {
    DB::purge($connection);
}
config([
    'database.default' => 'underground_press_test',
    'database.connections' => ['underground_press_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]],
    'cache.default' => 'array',
    'session.driver' => 'array',
]);
if (DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('Refusing to test against a persistent database.');
}

// Load only the domain schema, avoiding unrelated historical data migrations.
foreach ([
    '2026_04_04_000001_create_institutions_table.php',
    '2026_04_04_000002_create_prisoners_table.php',
    '2026_04_04_000003_create_prisoner_cases_table.php',
    '2026_04_07_000002_add_slug_to_prisoners_table.php',
    '2026_05_13_000001_add_under_review_to_prisoners.php',
    '2026_06_24_000001_add_date_precision_to_prisoner_tables.php',
    '2026_08_03_000001_add_imprisoned_for_months_to_prisoner_cases.php',
] as $migration) {
    (require database_path('migrations/'.$migration))->up();
}

$assertions = 0;
$check = function (bool $condition, string $message) use (&$assertions): void {
    $assertions++;
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$snapshot = fn () => json_encode([
    DB::table('prisoners')->orderBy('id')->get(),
    DB::table('prisoner_cases')->orderBy('id')->get(),
]);
$reset = function (): void {
    DB::table('prisoner_cases')->delete();
    DB::table('prisoners')->delete();
    Cache::flush();
};
$run = function (bool $dry = false) use ($check): string {
    $status = Artisan::call('prisoners:add-underground-press', $dry ? ['--dry-run' => true] : []);
    $check($status === 0, 'Import command failed.');

    return Artisan::output();
};
$cacheKeys = [PrisonerApiController::cacheKey(), 'museum:payload:v2', 'tracker:payload:v2:'.date('Y')];

$control = Prisoner::create(['name' => 'Existing Activist', 'description' => 'Preserve biography', 'sort_order' => 99]);
$control->cases()->create(['charges' => 'Preserve case']);
$before = $snapshot();
foreach ($cacheKeys as $key) {
    Cache::put($key, 'unchanged');
}
$check(str_contains($run(true), 'Would add profiles: 4'), 'Dry-run count is incorrect.');
$check($snapshot() === $before, 'Dry run changed database rows.');
foreach ($cacheKeys as $key) {
    $check(Cache::get($key) === 'unchanged', 'Dry run changed a cache.');
}

$run();
$check(Prisoner::count() === 5 && PrisonerCase::count() === 5, 'Expected four new profiles and four cases.');
$check($control->fresh()->description === 'Preserve biography', 'Existing biography changed.');
$check($control->fresh()->sort_order === 99, 'Existing curated position changed.');
$check($control->cases()->sole()->charges === 'Preserve case', 'Existing case changed.');
foreach (['Tom Forcade', 'Carlos Calderon', 'Jerome Friedman', 'Colin Neiburger'] as $name) {
    $person = Prisoner::where('name', $name)->sole();
    $case = $person->cases()->sole();
    $check(! $person->in_custody && ! $person->awaiting_trial && ! $person->imprisoned_or_exiled && $person->released, 'Historical detainee marked currently detained.');
    $check($person->sort_order > 99 && (bool) $person->slug, 'Missing public slug or appended position.');
    foreach (['website', 'birthdate', 'death_date', 'years_in_prison'] as $field) {
        $check($person->getRawOriginal($field) === null, 'Invented profile field: '.$field);
    }
    foreach (['arrest_date', 'incarceration_date', 'release_date', 'sentenced_date', 'imprisoned_for_days', 'imprisoned_for_months', 'institution_id'] as $field) {
        $check($case->$field === null, 'Invented custody endpoint, duration or institution: '.$field);
    }
    if ($name !== 'Tom Forcade') {
        $check($case->convicted === null, 'Assigned an unresolved criminal disposition.');
    }
}
$check(str_contains(Prisoner::where('name', 'Carlos Calderon')->sole()->cases()->sole()->sentence, 'two unnamed defendants'), 'Calderon source conflict omitted.');
$check(Prisoner::where('name', 'Colin Neiburger')->sole()->affiliation === null, 'Invented newspaper affiliation.');
foreach ($cacheKeys as $key) {
    $check(! Cache::has($key), 'Application cache was not invalidated.');
}
$applied = $snapshot();
$check(str_contains($run(), 'Added profiles: 0'), 'Replay added duplicates.');
$check($snapshot() === $applied, 'Replay changed records or timestamps.');

// Aliases, accents, reversed names and hidden profiles must participate in screening.
$reset();
$hidden = Prisoner::create(['name' => 'Already Reviewed Person', 'aka' => 'Kenneth Gary Goodson', 'under_review' => true, 'description' => 'Keep hidden profile']);
$hidden->cases()->create(['charges' => 'Keep hidden case']);
$accented = Prisoner::create(['name' => 'Calderón, Carlos', 'description' => 'Keep accented profile']);
$check(str_contains($run(), 'Added profiles: 2'), 'Alias/accent match was not preserved.');
$check(Prisoner::withoutGlobalScopes()->count() === 4, 'Duplicate identities created.');
$check($hidden->fresh()->description === 'Keep hidden profile' && $hidden->cases()->count() === 1, 'Hidden record overwritten.');
$check($accented->fresh()->description === 'Keep accented profile', 'Accent match overwritten.');

// An ambiguous identity must fail before any new person is written.
$reset();
Prisoner::create(['name' => 'Jerome Friedman']);
Prisoner::create(['name' => 'Separate Reviewed Person', 'aka' => 'Jerry Friedman', 'under_review' => true]);
$before = $snapshot();
try {
    $run();
    throw new RuntimeException('Expected ambiguous identity rejection.');
} catch (RuntimeException $exception) {
    $check(str_contains($exception->getMessage(), 'Ambiguous identity'), 'Unexpected ambiguity failure.');
}
$check($snapshot() === $before, 'Ambiguous batch partially wrote records.');

// A failure after an earlier successful insert must roll the entire batch back.
$reset();
DB::unprepared("CREATE TRIGGER fail_second_case BEFORE INSERT ON prisoner_cases WHEN NEW.charges LIKE 'Criminal syndicalism%' BEGIN SELECT RAISE(ABORT, 'Simulated case failure'); END");
try {
    $run();
    throw new RuntimeException('Expected simulated database failure.');
} catch (Throwable $exception) {
    $check(str_contains($exception->getMessage(), 'Simulated case failure'), 'Unexpected rollback failure.');
}
$check(Prisoner::withoutGlobalScopes()->count() === 0 && PrisonerCase::count() === 0, 'Failed import left partial records.');
DB::unprepared('DROP TRIGGER fail_second_case');

echo 'UNDERGROUND-PRESS-TESTS-OK: '.$assertions." assertions\n";
