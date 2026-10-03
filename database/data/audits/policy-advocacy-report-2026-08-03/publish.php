<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$apply = in_array('--apply', $argv, true);
$bodyPath = __DIR__.'/report.html';
$source = __DIR__.'/source-images/advocacy-gap-cover.jpg';
$bodyHash = '864eba37a7d9075b415ddb0c6a6e1483f168f37213e35fd63f70f46c846cc5e4';
$sourceHash = 'eb900bc5c06c2c35702884251b30075283f9d419d07d29f9194528fa572e1438';
$image = 'articles/advocacy-gap-political-prisoner-support-2026.jpg';

if (! is_file($bodyPath) || hash_file('sha256', $bodyPath) !== $bodyHash) {
    throw new RuntimeException('The reviewed report body is missing or has changed.');
}
if (! is_file($source) || hash_file('sha256', $source) !== $sourceHash) {
    throw new RuntimeException('The reviewed cover image is missing or has changed.');
}

$payload = [
    'title' => 'The Advocacy Gap: Why Political-Prisoner Support Must Become Proactive',
    'slug' => 'the-advocacy-gap-political-prisoner-support',
    'intro' => 'Political-prisoner organizations sustain vital correspondence, legal, financial and public-education work. But the movement still lacks a shared system for identifying cases early, assigning responsibility, tracking urgent needs and preparing release campaigns before a crisis.',
    'body' => file_get_contents($bodyPath),
    'published_at' => '2026-08-03 09:00:00',
    'image' => $image,
    'image_caption' => 'Participants in the Longest Walk rally at the U.S. Capitol, July 17, 1978. Photo by Bill Wilson; Washington Star Collection, D.C. Public Library, courtesy of the Washington Post. Published by Washington Area Spark and used with permission.',
    'citations_json' => [
        [
            'title' => 'NYC Anarchist Black Cross — PP/POW Updates and Announcements',
            'content' => 'https://nycabc.wordpress.com/pppow-updates-announcements/',
        ],
        [
            'title' => 'NYC Anarchist Black Cross — Illustrated Guide to Political Prisoners and Prisoners of War',
            'content' => 'https://nycabc.wordpress.com/guide/',
        ],
        [
            'title' => 'Anarchist Black Cross Federation — Warchest Program',
            'content' => 'https://www.abcf.net/warchest-program/',
        ],
        [
            'title' => 'ABCF — December 2025 Warchest Funds Report',
            'content' => 'https://www.abcf.net/wp-content/uploads/2026/01/Warchest-Funds-Report_Dec-2025.pdf',
        ],
        [
            'title' => 'Jericho Movement — Political Prisoners',
            'content' => 'https://www.thejerichomovement.com/prisoners',
        ],
        [
            'title' => 'U.S. Department of Justice OIG — Inspection of FCI Sheridan',
            'content' => 'https://oig.justice.gov/sites/default/files/reports/24-070_0.pdf',
        ],
        [
            'title' => 'Federal Bureau of Prisons — Compassionate Release/Reduction in Sentence',
            'content' => 'https://www2.fed.bop.gov/policy/progstat/5050_050_EN.pdf',
        ],
        [
            'title' => 'Office of the Pardon Attorney — How Clemency Works',
            'content' => 'https://www.justice.gov/pardon/how-clemency-works',
        ],
        [
            'title' => 'Office of the Pardon Attorney — Frequently Asked Questions',
            'content' => 'https://www.justice.gov/pardon/frequently-asked-questions',
        ],
        [
            'title' => 'Washington Area Spark — Retain Water Rights for Native Americans: 1978',
            'content' => 'https://www.flickr.com/photos/washington_area_spark/48650408157/',
        ],
    ],
];

$categories = Category::query()->where('slug', 'reports')->get();
if ($categories->count() !== 1 || $categories->first()->title !== 'Reports') {
    throw new RuntimeException('Expected one exact Reports category.');
}
$category = $categories->first();

$authors = Author::query()->where('name', 'National Political Prisoner Coalition')->get();
if ($authors->count() !== 1) {
    throw new RuntimeException('Expected one exact National Political Prisoner Coalition author.');
}
$author = $authors->first();

$payload['category_id'] = $category->id;
$payload['author_id'] = $author->id;

$matches = Article::query()->where('slug', $payload['slug'])->get();
if ($matches->count() > 1) {
    throw new RuntimeException('Duplicate article slugs exist.');
}

$expected = $payload;
$isExact = static function (Article $article) use ($expected, $sourceHash): bool {
    foreach ($expected as $field => $value) {
        $actual = $field === 'citations_json' ? $article->citations_json : $article->getRawOriginal($field);
        if ($field === 'published_at') {
            $actual = $article->published_at?->format('Y-m-d H:i:s');
        }
        if ($actual !== $value) {
            return false;
        }
    }

    $path = Storage::disk('public')->path($expected['image']);
    return is_file($path) && hash_file('sha256', $path) === $sourceHash;
};

$existing = $matches->first();
if ($existing) {
    if (! $isExact($existing)) {
        throw new RuntimeException('An article with this slug exists but differs from the reviewed report.');
    }

    echo json_encode([
        'mode' => $apply ? 'apply' : 'preflight',
        'status' => 'already-published',
        'article_id' => $existing->id,
        'url' => $existing->url,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
    exit(0);
}

if (Article::query()->where('image', $image)->exists()) {
    throw new RuntimeException('The destination image path is already used by another article.');
}

$destination = Storage::disk('public')->path($image);
if (is_file($destination) && hash_file('sha256', $destination) !== $sourceHash) {
    throw new RuntimeException('The destination image exists with different content.');
}

if (! $apply) {
    echo json_encode([
        'mode' => 'preflight',
        'status' => 'ready',
        'title' => $payload['title'],
        'slug' => $payload['slug'],
        'published_at' => $payload['published_at'],
        'category' => $category->title,
        'author' => $author->name,
        'image' => $image,
        'image_sha256' => $sourceHash,
        'body_sha256' => $bodyHash,
        'citation_count' => count($payload['citations_json']),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
    exit(0);
}

$backupDir = storage_path('app/backups');
if (! is_dir($backupDir) && ! mkdir($backupDir, 0775, true) && ! is_dir($backupDir)) {
    throw new RuntimeException('Could not create the backup directory.');
}
$backup = $backupDir.'/before-advocacy-gap-report-'.gmdate('Ymd-His').'.sqlite';
if (! copy(database_path('database.sqlite'), $backup)) {
    throw new RuntimeException('Could not create a database backup.');
}

$destinationDir = dirname($destination);
if (! is_dir($destinationDir) && ! mkdir($destinationDir, 0775, true) && ! is_dir($destinationDir)) {
    throw new RuntimeException('Could not create the article image directory.');
}
if (! copy($source, $destination) || hash_file('sha256', $destination) !== $sourceHash) {
    throw new RuntimeException('Could not install and verify the report image.');
}

$article = DB::transaction(static fn (): Article => Article::query()->create($payload));
$verified = Article::query()->whereKey($article->id)->firstOrFail();
if (! $isExact($verified)) {
    throw new RuntimeException('Post-publication verification failed.');
}

echo json_encode([
    'mode' => 'applied',
    'status' => 'published',
    'article_id' => $verified->id,
    'title' => $verified->title,
    'url' => $verified->url,
    'published_at' => $verified->published_at?->format('Y-m-d H:i:s'),
    'image' => $verified->image,
    'image_sha256' => hash_file('sha256', $destination),
    'backup' => $backup,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
