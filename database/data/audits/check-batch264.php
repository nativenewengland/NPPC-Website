<?php
// Run from any directory: php database/data/audits/check-batch264.php
// All writes and cache operations use disposable in-memory stores.
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

foreach (array_keys(config('database.connections')) as $connection) { DB::purge($connection); }
config(['database.connections' => ['batch264_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]]);
DB::setDefaultConnection('batch264_test');
Cache::swap(new Illuminate\Cache\Repository(new Illuminate\Cache\ArrayStore));
$db = DB::connection();
$check = function ($ok, $message) {
    if (! $ok) { throw new RuntimeException($message); }
    echo 'PASS: '.$message."\n";
};
$check($db->select('PRAGMA database_list')[0]->file === '', 'Only in-memory SQLite is connected');
$db->statement('CREATE TABLE prisoners (id TEXT PRIMARY KEY, slug TEXT UNIQUE, name TEXT, birthdate TEXT, death_date TEXT, date_precision TEXT, under_review INTEGER, description TEXT, photo TEXT, website TEXT, in_custody INTEGER, released INTEGER, updated_at TEXT)');
$input = json_decode(file_get_contents(__DIR__.'/../fixes/batch264.json'), true, 512, JSON_THROW_ON_ERROR);
$script = file_get_contents(__DIR__.'/../run-batch-264.sh');
preg_match("/UPDATE_CODE='\n(.*?)\n'/s", $script, $match);
$check(isset($match[1]) && ! str_contains($match[1], "'"), 'Tinker block has no apostrophes');
$code = str_replace('File::get(base_path("database/data/fixes/batch264.json"))', '$sourceJson', $match[1]);
$sourceJson = json_encode($input);
$seed = function () use ($db, $input) {
    $db->table('prisoners')->delete();
    foreach ($input['entries'] as $entry) {
        $db->table('prisoners')->insert([
            'id' => $entry['id'], 'slug' => $entry['slug'], 'name' => $entry['name'],
            'birthdate' => null, 'death_date' => null, 'date_precision' => null, 'under_review' => 0,
            'description' => 'Preserve this biography exactly.', 'photo' => 'prisoners/curated.jpg',
            'website' => 'https://example.org/support', 'in_custody' => 1, 'released' => 0,
            'updated_at' => '2020-01-01 00:00:00',
        ]);
    }
};
$snapshot = fn () => $db->table('prisoners')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
$run = function ($dry) use ($code, &$sourceJson) {
    putenv('NPPC_BATCH_DRY_RUN='.($dry ? '1' : '0'));
    ob_start();
    try { eval($code); } finally { $out = ob_get_clean(); }
    if (! str_contains($out, 'B264-OK')) { throw new RuntimeException('Missing success sentinel'); }
    return $out;
};
$seed();
$before = $snapshot();
Cache::put(PrisonerApiController::cacheKey(), 'cached');
Cache::put('museum:payload:v2', 'cached');
$preview = $run(true);
$check($snapshot() === $before && Cache::get(PrisonerApiController::cacheKey()) === 'cached' && Cache::get('museum:payload:v2') === 'cached', 'Dry run preserves data and caches');
$check(str_contains($preview, 'Would update rows: 100') && str_contains($preview, 'Would fill date fields: 162'), 'Dry run reports 100 people and 162 dates');
$run(false);
$after = $snapshot();
$count = 0;
foreach ($input['entries'] as $entry) {
    $record = Prisoner::withoutGlobalScopes()->whereKey($entry['id'])->sole();
    foreach ($entry['dates'] as $field => $parts) {
        if ($record->partialDateIso($field) !== sprintf('%04d-%02d-%02d', $parts['year'], $parts['month'], $parts['day'])) {
            throw new RuntimeException('Wrong date or precision: '.$entry['slug'].' '.$field);
        }
        $count++;
    }
}
$check($count === 162, 'All 162 dates stored at day precision');
foreach ($before as $i => $row) {
    foreach ($row as $field => $value) {
        if (! in_array($field, ['birthdate', 'death_date', 'date_precision', 'updated_at'], true) && $after[$i][$field] !== $value) {
            throw new RuntimeException('Unexpected field changed: '.$field);
        }
    }
}
$check(count($after) === 100, 'Existing profiles only; biographies, photos, websites and custody flags unchanged');
$check(Cache::get(PrisonerApiController::cacheKey()) === null && Cache::get('museum:payload:v2') === null, 'Changed data invalidates both caches');
$out = $run(false);
$check($snapshot() === $after && str_contains($out, 'Updated rows: 0') && str_contains($out, 'Filled date fields: 0'), 'Replay preserves every field including timestamps');

// Populate every requested field, including partial dates, before replaying.
$seed();
foreach ($input['entries'] as $i => $entry) {
    $db->table('prisoners')->where('id', $entry['id'])->update([
        'birthdate' => '1750-01-01', 'death_date' => '2020-01-01',
        'date_precision' => json_encode(['birthdate' => $i % 2 ? 'day' : 'year', 'death_date' => 'year']),
    ]);
}
$populated = $snapshot();
$run(false);
$check($snapshot() === $populated, 'Populated full and partial dates are never replaced');

// Fill a missing date alongside an existing partial date without losing precision.
$seed();
$deathEntry = current(array_filter($input['entries'], fn ($entry) => isset($entry['dates']['death_date'])));
$db->table('prisoners')->where('id', $deathEntry['id'])->update(['birthdate' => '1750-01-01', 'date_precision' => json_encode(['birthdate' => 'year'])]);
$run(false);
$record = Prisoner::withoutGlobalScopes()->whereKey($deathEntry['id'])->sole();
$check($record->partialDateIso('birthdate') === '1750' && $record->partialDateIso('death_date') !== null, 'Filling a death date preserves the existing birth-year precision');

foreach (['identity', 'review', 'date', 'prose', 'duplicate', 'source', 'chronology', 'late-write'] as $scenario) {
    $seed();
    $bad = $input;
    $last = $input['entries'][99];
    if ($scenario === 'identity') { $db->table('prisoners')->where('id', $last['id'])->update(['name' => 'Different person']); }
    if ($scenario === 'review') { $db->table('prisoners')->where('id', $last['id'])->update(['under_review' => 1]); }
    if ($scenario === 'date') { $bad['entries'][99]['dates'][array_key_first($last['dates'])] = ['year' => 1900, 'month' => 2, 'day' => 30]; }
    if ($scenario === 'prose') { $bad['entries'][99]['description'] = 'Disallowed edit'; }
    if ($scenario === 'duplicate') { $bad['entries'][99] = $bad['entries'][0]; }
    if ($scenario === 'source') { $bad['entries'][99]['sources'] = []; }
    if ($scenario === 'chronology') { $db->table('prisoners')->where('id', $deathEntry['id'])->update(['birthdate' => '2026-01-01']); }
    if ($scenario === 'late-write') {
        $db->unprepared('CREATE TRIGGER fail_last BEFORE UPDATE ON prisoners WHEN NEW.id = '.$db->getPdo()->quote($last['id'])." BEGIN SELECT RAISE(ABORT, 'test late failure'); END");
    }
    $sourceJson = json_encode($bad);
    $before = $snapshot();
    Cache::put(PrisonerApiController::cacheKey(), 'cached');
    $failed = false;
    try { $run(false); } catch (Throwable $e) { $failed = true; }
    $check($failed && $snapshot() === $before && Cache::get(PrisonerApiController::cacheKey()) === 'cached', 'Atomic rejection: '.$scenario);
    if ($scenario === 'late-write') { $db->unprepared('DROP TRIGGER fail_last'); }
}
echo "B264-TESTS-OK\n";
