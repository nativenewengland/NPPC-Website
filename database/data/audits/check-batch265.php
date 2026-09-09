<?php
// php database/data/audits/check-batch265.php
// All writes use isolated in-memory SQLite and an in-memory cache.
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrisonerCase;
use App\Http\Controllers\Api\PrisonerApiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

foreach (array_keys(config('database.connections')) as $connection) { DB::purge($connection); }
config(['database.connections' => ['batch265_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]]);
DB::setDefaultConnection('batch265_test');
Cache::swap(new Illuminate\Cache\Repository(new Illuminate\Cache\ArrayStore));
$db = DB::connection();
$assertions = 0;
$check = function ($ok, $message) use (&$assertions) {
    if (! $ok) { throw new RuntimeException($message); }
    $assertions++;
};
$check($db->select('PRAGMA database_list')[0]->file === '', 'Only in-memory SQLite is connected');
$input = json_decode(file_get_contents(__DIR__.'/../fixes/batch265.json'), true, 512, JSON_THROW_ON_ERROR);
$script = file_get_contents(__DIR__.'/../run-batch-265.sh');
preg_match("/UPDATE_CODE='\n(.*?)\n'/s", $script, $match);
$check(isset($match[1]) && ! str_contains($match[1], "'"), 'Tinker block has no apostrophes');
$code = str_replace('File::get(base_path("database/data/fixes/batch265.json"))', '$sourceJson', $match[1]);
$sourceJson = json_encode($input);
$db->statement('CREATE TABLE prisoners (id TEXT PRIMARY KEY, slug TEXT UNIQUE, name TEXT, under_review INTEGER, description TEXT, photo TEXT, website TEXT, currently_in_exile INTEGER, in_custody INTEGER, released INTEGER, updated_at TEXT)');
$columns = [];
foreach ($input['entries'][0]['expected'] as $field => $value) {
    $columns[] = '"'.$field.'" '.(in_array($field, ['imprisoned_for_days', 'in_exile_for_days', 'imprisoned_for_months'], true) ? 'INTEGER' : 'TEXT').($field === 'id' ? ' PRIMARY KEY' : '');
}
$db->statement('CREATE TABLE prisoner_cases ('.implode(', ', $columns).')');
$seed = function () use ($db, $input) {
    $db->table('prisoner_cases')->delete();
    $db->table('prisoners')->delete();
    $seen = [];
    foreach ($input['entries'] as $entry) {
        $p = $entry['prisoner'];
        if (! isset($seen[$p['id']])) {
            $db->table('prisoners')->insert($p + ['under_review' => 0, 'description' => 'Keep the biography exactly.', 'photo' => 'curated.jpg', 'website' => 'https://example.org/support', 'currently_in_exile' => 1, 'in_custody' => 0, 'released' => 1, 'updated_at' => '2000-01-01']);
            $seen[$p['id']] = true;
        }
        $db->table('prisoner_cases')->insert($entry['expected']);
    }
    $unrelated = $input['entries'][0]['expected'];
    $unrelated['id'] = 'unrelated-case';
    $unrelated['charges'] = 'Different prosecution; preserve all fields';
    $db->table('prisoner_cases')->insert($unrelated);
};
$snapshot = fn ($table) => $db->table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
$run = function ($dry) use ($code, &$sourceJson) {
    putenv('NPPC_BATCH_DRY_RUN='.($dry ? '1' : '0'));
    ob_start();
    try { eval($code); } finally { $out = ob_get_clean(); }
    if (! str_contains($out, 'B265-OK')) { throw new RuntimeException('Missing success sentinel'); }
    return $out;
};
$keys = [PrisonerApiController::cacheKey(), 'museum:payload:v2', 'tracker:payload:v2:'.date('Y')];
$seed();
$before = $snapshot('prisoner_cases');
$profiles = $snapshot('prisoners');
foreach ($keys as $key) { Cache::put($key, 'cached'); }
$out = $run(true);
$check($snapshot('prisoner_cases') === $before && $snapshot('prisoners') === $profiles, 'Dry run preserves every row');
$check(str_contains($out, 'Would update cases: 27') && str_contains($out, 'Date fields: 87; case date phrases: 8'), 'Preview counts');
foreach ($keys as $key) { $check(Cache::get($key) === 'cached', 'Dry run preserves cache'); }
$run(false);
$after = $snapshot('prisoner_cases');
$check($snapshot('prisoners') === $profiles, 'All profile fields remain unchanged');
$check(count($after) === count($before), 'No case created or deleted');
foreach ($keys as $key) { $check(Cache::get($key) === null, 'Applied batch clears cache'); }
$entries = array_column($input['entries'], null, 'id');
foreach ($before as $i => $row) {
    $entry = $entries[$row['id']] ?? null;
    if (! $entry) { $check($after[$i] === $row, 'Unrelated case preserved'); continue; }
    $record = PrisonerCase::withoutGlobalScopes()->whereKey($row['id'])->sole();
    foreach ($entry['dates'] as $field => $change) { $check($record->partialDateIso($field) === $change['value'], 'Correct date and precision: '.$field); }
    foreach ($entry['text_edits'] as $edit) { $check(str_contains($record->sentence, $edit['new']) && ! str_contains($record->sentence, $edit['old']), 'Exact date phrase corrected'); }
    $allowed = array_merge(array_keys($entry['dates']), ['date_precision', 'updated_at']);
    if ($entry['text_edits']) { $allowed[] = 'sentence'; }
    if (array_intersect(array_keys($entry['dates']), ['incarceration_date', 'release_date', 'death_in_custody_date'])) {
        $allowed[] = 'imprisoned_for_days';
        $oracle = $record->incarceration_date ? (int) ((strtotime($record->getRawOriginal('release_date')) - strtotime($record->getRawOriginal('incarceration_date'))) / 86400) : null;
        $check($record->imprisoned_for_days === $oracle, 'Derived duration agrees with independent date subtraction');
    }
    foreach ($row as $field => $value) {
        if (! in_array($field, $allowed, true)) { $check($after[$i][$field] === $value, 'Unrelated field preserved: '.$field); }
    }
}
$out = $run(false);
$check($snapshot('prisoner_cases') === $after && str_contains($out, 'Updated cases: 0'), 'Idempotent replay preserves timestamps');
// A corrected row may acquire unrelated prose later; replay must preserve it.
$db->table('prisoner_cases')->where('id', $input['entries'][26]['id'])->update(['charges' => 'Later curated wording']);
$later = $snapshot('prisoner_cases');
$run(false);
$check($snapshot('prisoner_cases') === $later, 'Replay preserves later unrelated curation');

foreach (['identity', 'review', 'missing', 'date', 'source', 'prose', 'duplicate', 'stale', 'precision', 'text', 'chronology', 'late-write'] as $scenario) {
    $seed();
    $bad = $input;
    $last = $input['entries'][26];
    if ($scenario === 'identity') { $db->table('prisoners')->where('id', $last['prisoner']['id'])->update(['name' => 'Different person']); }
    if ($scenario === 'review') { $db->table('prisoners')->where('id', $last['prisoner']['id'])->update(['under_review' => 1]); }
    if ($scenario === 'missing') { $db->table('prisoner_cases')->where('id', $last['id'])->delete(); }
    if ($scenario === 'date') { $bad['entries'][26]['dates'][array_key_first($last['dates'])]['value'] = '2019-02-30'; }
    if ($scenario === 'source') { $bad['entries'][26]['dates'][array_key_first($last['dates'])]['sources'] = []; }
    if ($scenario === 'prose') { $bad['entries'][26]['description'] = 'Forbidden biography edit'; }
    if ($scenario === 'duplicate') { $bad['entries'][26] = $bad['entries'][0]; }
    if ($scenario === 'stale') { $db->table('prisoner_cases')->where('id', $last['id'])->update(['release_date' => '2015-01-12']); }
    if ($scenario === 'precision') { $db->table('prisoner_cases')->where('id', $last['id'])->update(['date_precision' => '{"release_date":"year"}']); }
    if ($scenario === 'text') { $db->table('prisoner_cases')->where('id', $input['entries'][1]['id'])->update(['sentence' => 'New researched wording']); }
    if ($scenario === 'chronology') { $bad['entries'][26]['dates']['release_date']['value'] = '1900-01-01'; }
    if ($scenario === 'late-write') {
        $db->unprepared('CREATE TRIGGER fail_last BEFORE UPDATE ON prisoner_cases WHEN NEW.id = '.$db->getPdo()->quote($last['id'])." BEGIN SELECT RAISE(ABORT, 'test late failure'); END");
    }
    $sourceJson = json_encode($bad);
    $before = $snapshot('prisoner_cases');
    $profiles = $snapshot('prisoners');
    foreach ($keys as $key) { Cache::put($key, 'cached'); }
    $failed = false;
    try { $run(false); } catch (Throwable $e) { $failed = true; }
    $check($failed && $snapshot('prisoner_cases') === $before && $snapshot('prisoners') === $profiles, 'Atomic rejection: '.$scenario);
    foreach ($keys as $key) { $check(Cache::get($key) === 'cached', 'Rejected batch preserves cache'); }
    if ($scenario === 'late-write') { $db->unprepared('DROP TRIGGER fail_last'); }
}
echo 'B265-TESTS-OK: '.$assertions." assertions\n";
