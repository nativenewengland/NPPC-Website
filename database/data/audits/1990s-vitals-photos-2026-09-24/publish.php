<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$entries = [
    [
        'id' => '1766e8ba-1156-4eaf-9c1f-16ba27224f40',
        'name' => 'Hasan Shakur',
        'expect' => ['birthdate' => null, 'photo' => null, 'death_date' => '2006-08-31 00:00:00'],
        'dates' => ['birthdate' => ['1977-04-28', 'day']],
        'photo' => [
            'file' => 'hasan-shakur.jpg',
            'path' => 'prisoners/1990s-research/hasan-shakur.jpg',
            'sha256' => 'd4443fa93255baa248230914db95c02fd565c5947995b3bfaaa6de86e1ee07ad',
        ],
        'credit' => '<h2>Photo and birth-date source</h2><p><a href="https://www.tdcj.texas.gov/death_row/dr_info/frazierderrick.html">Texas Department of Criminal Justice death-row record for Derrick Wayne Frazier</a>. The official record gives his birth date as April 28, 1977 and supplies the inmate portrait shown here.</p>',
    ],
    [
        'id' => '81ffadd2-3973-4f6f-8a1a-b118e20a6177',
        'name' => 'Peter Dougherty',
        'expect' => ['birthdate' => null],
        'dates' => ['birthdate' => ['1934-08-22', 'day']],
        'credit' => '<h2>Birth-date source</h2><p><a href="https://www.jamnalalbajajfoundation.org/awards/archives/2009/international/charles-peter-dougherty">Jamnalal Bajaj Foundation, 2009 international award biography</a>. The foundation records Charles Peter Dougherty\'s birth on August 22, 1934.</p>',
    ],
    [
        'id' => '7593507d-edda-4e92-ac77-afbe1373d7c5',
        'name' => 'Ramsey Muñiz',
        'expect' => ['death_date' => null, 'birthdate' => '1942-12-13 00:00:00'],
        'dates' => ['death_date' => ['2022-10-02', 'day']],
        'credit' => '<h2>Death-date sources</h2><ul><li><a href="https://www.seasidefuneral.com/obituaries/Ramiro-R-Ramsey-Muiz?obId=33569276">Seaside Funeral Home obituary for Ramiro R. “Ramsey” Muñiz</a></li><li><a href="https://www.tpr.org/government-politics/2022-10-05/ramiro-ramsey-muniz-history-making-latino-activist-dies-at-79">Texas Public Radio, October 5, 2022</a></li></ul>',
    ],
    [
        'id' => 'fa31ff90-53a2-4b63-80ef-5d18ef065839',
        'name' => 'Aimee Allison',
        'expect' => ['photo' => null],
        'dates' => [],
        'photo' => [
            'file' => 'aimee-allison.jpg',
            'path' => 'prisoners/1990s-research/aimee-allison.jpg',
            'sha256' => '193f2f4d9511721b9967285e1e517730f464201db9c7933202351d5dcd1bc088',
        ],
        'credit' => '<h2>Photo credit</h2><p><a href="https://www.aimeeallison.com/aimee">Aimee Allison official biography</a>. Photograph by Justin Buell; portrait crop, with no AI editing.</p>',
    ],
    [
        'id' => '0ef75262-98df-483e-9926-ee49305c8c34',
        'name' => 'Yolanda Huet-Vaughn',
        'expect' => ['photo' => null],
        'dates' => [],
        'photo' => [
            'file' => 'yolanda-huet-vaughn.jpg',
            'path' => 'prisoners/1990s-research/yolanda-huet-vaughn.jpg',
            'sha256' => 'a770e67e365f9639372d2599f46bbd3a561e07dc3627bc7162850beecf629436',
        ],
        'credit' => '<h2>Photo credit</h2><p><a href="https://www.flickr.com/photos/pnhp_national/8483916668">Yolanda Huet-Vaughn, Art Chen, and Floyd Huen at the 2008 Physicians for a National Health Program annual meeting</a>. Photograph by Mark Almberg / PNHP; Huet-Vaughn is identified by her name badge and was cropped from the group photograph. No AI editing.</p>',
    ],
];

foreach ($entries as $entry) {
    $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
    if ($prisoner->name !== $entry['name']) {
        throw new RuntimeException('Identity mismatch: '.$entry['name']);
    }
    foreach ($entry['expect'] as $field => $expected) {
        if ($prisoner->getRawOriginal($field) !== $expected) {
            throw new RuntimeException("Concurrent change: {$entry['name']} {$field}");
        }
    }
    if (isset($entry['photo'])) {
        $source = __DIR__.'/'.$entry['photo']['file'];
        if (hash_file('sha256', $source) !== $entry['photo']['sha256']) {
            throw new RuntimeException('Image checksum mismatch: '.$entry['name']);
        }
        if (Storage::disk('public')->exists($entry['photo']['path'])) {
            throw new RuntimeException('Image destination already exists: '.$entry['name']);
        }
    }
}

$backup = storage_path('app/backups/before-1990s-vitals-photos-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$results = DB::transaction(function () use ($entries) {
    $results = [];
    foreach ($entries as $entry) {
        $prisoner = Prisoner::withoutGlobalScopes()->findOrFail($entry['id']);
        $before = $prisoner->getAttributes();
        $precision = $prisoner->date_precision ?? [];

        foreach ($entry['dates'] as $field => [$date, $datePrecision]) {
            $prisoner->{$field} = $date;
            $precision[$field] = $datePrecision;
        }
        if ($entry['dates']) {
            $prisoner->date_precision = $precision;
        }
        if (isset($entry['photo'])) {
            $source = __DIR__.'/'.$entry['photo']['file'];
            if (! Storage::disk('public')->put($entry['photo']['path'], file_get_contents($source))) {
                throw new RuntimeException('Image upload failed: '.$entry['name']);
            }
            $prisoner->photo = $entry['photo']['path'];
        }
        $prisoner->body = ($prisoner->body ?? '').$entry['credit'];
        $prisoner->save();
        $prisoner->refresh();

        $allowed = array_merge(array_keys($entry['dates']), ['date_precision', 'age', 'photo', 'body', 'updated_at']);
        foreach ($before as $field => $value) {
            if (! in_array($field, $allowed, true) && $prisoner->getRawOriginal($field) !== $value) {
                throw new RuntimeException("Unexpected change: {$entry['name']} {$field}");
            }
        }
        $results[] = [
            'id' => $prisoner->id,
            'name' => $prisoner->name,
            'birthdate' => $prisoner->getRawOriginal('birthdate'),
            'death_date' => $prisoner->getRawOriginal('death_date'),
            'date_precision' => $prisoner->getRawOriginal('date_precision'),
            'photo' => $prisoner->photo,
            'photo_sha256' => $prisoner->photo ? hash_file('sha256', Storage::disk('public')->path($prisoner->photo)) : null,
        ];
    }
    return $results;
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode(['backup' => $backup, 'updated' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
