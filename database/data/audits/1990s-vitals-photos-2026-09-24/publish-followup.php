<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$specs = [
    [
        'id' => 'af3947d6-5760-479b-835b-c8276e498e59',
        'name' => 'Helen Woodson',
        'expect' => ['birthdate' => '1944-06-26 00:00:00', 'death_date' => null],
        'dates' => ['birthdate' => ['1943-06-26', 'day'], 'death_date' => ['2023-12-02', 'day']],
        'source' => '<h2>Birth and death date sources</h2><ul><li><a href="https://www.nukeresister.org/2024/05/15/10676/">The Nuclear Resister, Helen Dery Woodson, Presente!</a> — June 26, 1943–December 2, 2023.</li><li><a href="https://www.ncronline.org/news/helen-woodson-plowshares-anti-nuclear-activist-who-spent-27-years-jail-dies">National Catholic Reporter obituary</a> — independently confirms her December 2 death at age 80.</li></ul>',
    ],
];

foreach ($specs as $spec) {
    $p = Prisoner::withoutGlobalScopes()->findOrFail($spec['id']);
    if ($p->name !== $spec['name']) throw new RuntimeException('Identity mismatch');
    foreach ($spec['expect'] as $field => $value) {
        if ($p->getRawOriginal($field) !== $value) throw new RuntimeException("Concurrent change: {$field}");
    }
}

$results = DB::transaction(function () use ($specs) {
    $results = [];
    foreach ($specs as $spec) {
        $p = Prisoner::withoutGlobalScopes()->findOrFail($spec['id']);
        $before = $p->getAttributes();
        $precision = $p->date_precision ?? [];
        foreach ($spec['dates'] as $field => [$date, $value]) {
            $p->{$field} = $date;
            $precision[$field] = $value;
        }
        $p->date_precision = $precision;
        $p->body = ($p->body ?? '').$spec['source'];
        $p->save();
        $p->refresh();
        foreach ($before as $field => $value) {
            if (! in_array($field, ['birthdate', 'death_date', 'date_precision', 'age', 'body', 'updated_at'], true)
                && $p->getRawOriginal($field) !== $value) {
                throw new RuntimeException("Unexpected change: {$field}");
            }
        }
        $results[] = ['name' => $p->name, 'birthdate' => $p->getRawOriginal('birthdate'), 'death_date' => $p->getRawOriginal('death_date'), 'date_precision' => $p->getRawOriginal('date_precision')];
    }

    // These dates were already exact before this pass; make the precision metadata explicit.
    foreach ([
        ['id' => '1766e8ba-1156-4eaf-9c1f-16ba27224f40', 'name' => 'Hasan Shakur', 'expected' => '{"birthdate":"day"}', 'precision' => ['birthdate' => 'day', 'death_date' => 'day']],
        ['id' => '7593507d-edda-4e92-ac77-afbe1373d7c5', 'name' => 'Ramsey Muñiz', 'expected' => '{"death_date":"day"}', 'precision' => ['birthdate' => 'day', 'death_date' => 'day']],
    ] as $item) {
        $p = Prisoner::withoutGlobalScopes()->findOrFail($item['id']);
        if ($p->name !== $item['name'] || $p->getRawOriginal('date_precision') !== $item['expected']) {
            throw new RuntimeException('Precision snapshot changed: '.$item['name']);
        }
        $p->date_precision = $item['precision'];
        $p->save();
        $results[] = ['name' => $p->name, 'date_precision' => $p->getRawOriginal('date_precision')];
    }
    return $results;
});

Cache::forget(App\Http\Controllers\Api\PrisonerApiController::cacheKey());
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
