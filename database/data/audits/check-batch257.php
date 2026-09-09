<?php
// Isolated in-memory SQLite checks. Never connects these models to production.
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.connections.batch257_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
Illuminate\Support\Facades\DB::setDefaultConnection('batch257_test');
Illuminate\Support\Facades\Cache::swap(new Illuminate\Cache\Repository(new Illuminate\Cache\ArrayStore));
$db = Illuminate\Support\Facades\DB::connection();
$db->statement('CREATE TABLE authors (id TEXT PRIMARY KEY, name TEXT, slug TEXT UNIQUE, avatar TEXT)');
$db->statement('CREATE TABLE articles (id TEXT PRIMARY KEY, slug TEXT UNIQUE, author_id TEXT REFERENCES authors(id), body TEXT, image TEXT, published_at TEXT, updated_at TEXT)');
$payload = json_decode(file_get_contents(__DIR__.'/../fixes/batch257.json'), true);
$script = file_get_contents(__DIR__.'/../run-batch-257.sh');
preg_match("/UPDATE_CODE='\n(.*?)\n'/s", $script, $match);
$code = str_replace('File::get(base_path("database/data/fixes/batch257.json"))', 'base64_decode("'.base64_encode(json_encode($payload)).'")', $match[1]);
$check = function ($ok, $message) { if (! $ok) { throw new RuntimeException($message); } };
$seed = function () use ($db, $payload) {
    $db->table('articles')->delete();
    $db->table('authors')->delete();
    foreach (['source', 'target'] as $key) {
        $db->table('authors')->insert($payload[$key] + ['avatar' => $key.'.png']);
    }
    foreach ($payload['articles'] as $i => $slug) {
        $db->table('articles')->insert(['id' => 'article-'.$i, 'slug' => $slug, 'author_id' => $payload['source']['id'], 'body' => 'Preserve this biography and text.', 'image' => 'unchanged.png', 'published_at' => '2024-11-20', 'updated_at' => '2026-01-01']);
    }
};
$snapshot = fn () => json_encode([$db->table('authors')->orderBy('id')->get(), $db->table('articles')->orderBy('id')->get()]);
$run = function ($dry) use ($code) {
    putenv('NPPC_BATCH_DRY_RUN='.($dry ? '1' : '0'));
    ob_start();
    try { eval($code); } finally { ob_end_clean(); }
};
$seed();
$before = $snapshot();
$run(true);
$check($snapshot() === $before, 'Dry run changed data.');
$targetBefore = $db->table('authors')->where('id', $payload['target']['id'])->first();
$articlesBefore = $db->table('articles')->orderBy('id')->get()->all();
$run(false);
$check($db->table('authors')->count() === 1, 'Duplicate not removed.');
$check($db->table('authors')->first() == $targetBefore, 'Canonical author changed.');
foreach ($articlesBefore as $row) { $row->author_id = $payload['target']['id']; }
$check($db->table('articles')->orderBy('id')->get()->all() == $articlesBefore, 'Unexpected article field changed.');
$after = $snapshot();
$run(false);
$check($snapshot() === $after, 'Replay changed data.');
foreach (['unexpected-author', 'extra-article'] as $scenario) {
    $seed();
    if ($scenario === 'unexpected-author') {
        $db->table('articles')->where('id', 'article-0')->update(['author_id' => null]);
    } else {
        $db->table('articles')->insert(['id' => 'extra', 'slug' => 'extra', 'author_id' => $payload['source']['id']]);
    }
    $before = $snapshot();
    $thrown = false;
    try { $run(false); } catch (RuntimeException $e) { $thrown = true; }
    $check($thrown && $snapshot() === $before, 'Guard failed: '.$scenario);
}
echo "B257-TESTS-OK\n";
