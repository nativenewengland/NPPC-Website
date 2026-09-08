<?php

// No database writes or queries: exercise the batch planner on in-memory models.
// Run from the repository: php database/data/audits/check-batch255.php
require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function check255(bool $condition, string $label): void
{
    if (! $condition) {
        throw new RuntimeException('FAILED: '.$label);
    }
    echo 'PASS: '.$label."\n";
}

$script = file_get_contents(__DIR__.'/../run-batch-255.sh');
preg_match("/UPDATE_CODE='\n(.*?)\n'/s", $script, $match);
check255(isset($match[1]) && ! str_contains($match[1], "'"), 'tinker block contains no apostrophes');
$boundary = '$changed = DB::transaction';
check255(substr_count($match[1], $boundary) === 1, 'planner boundary is unambiguous');
// Evaluate validation and planner definitions only, never the persistence section.
eval(explode($boundary, $match[1], 2)[0]);

$total = 0;
foreach ($payload['entries'] as $entry) {
    $base = array_fill_keys(array_merge($textFields, $dateFields), null);
    $base += [
        'id' => $entry['case_id'], 'prisoner_id' => $entry['id'],
        'charges' => $entry['match_charges'], 'release_date' => '1980-04-03 00:00:00',
        'date_precision' => json_encode(['release_date' => 'month']),
        'institution_id' => 'preserved institution', 'imprisoned_for_days' => 12,
        'imprisoned_for_months' => 0, 'in_exile_for_days' => 42,
        'created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-02 00:00:00',
    ];
    $record = new App\Models\PrisonerCase;
    $record->setRawAttributes($base, true);
    $dirty = $planCase($record, $entry);
    $expected = array_merge(array_keys($entry['fields']), array_keys($entry['dates']));
    $changedFields = array_values(array_diff(array_keys($dirty), ['date_precision']));
    sort($expected);
    sort($changedFields);
    check255($expected === $changedFields, 'fills exactly proposed fields: '.$entry['name']);
    $total += count($changedFields);
    foreach ($base as $field => $value) {
        if (! array_key_exists($field, $dirty)) {
            check255($record->getAttributes()[$field] === $value, 'preserves '.$entry['slug'].' '.$field);
        }
    }
    check255($record->date_precision['release_date'] === 'month', 'preserves unrelated precision');
    foreach ($entry['dates'] as $field => $parts) {
        $iso = sprintf('%04d', $parts['year']);
        if (isset($parts['month'])) { $iso .= sprintf('-%02d', $parts['month']); }
        if (isset($parts['day'])) { $iso .= sprintf('-%02d', $parts['day']); }
        check255($record->partialDateIso($field) === $iso, 'retains source precision: '.$field);
    }
    $record->syncOriginal();
    $after = $record->getAttributes();
    ob_start();
    $again = $planCase($record, $entry);
    ob_end_clean();
    check255($again === [] && $after === $record->getAttributes(), 'idempotent replay: '.$entry['name']);

    $filled = $base;
    foreach ($entry['fields'] as $field => $value) { $filled[$field] = 'Existing user value'; }
    foreach ($entry['dates'] as $field => $value) { $filled[$field] = '1950-05-06 00:00:00'; }
    $record->setRawAttributes($filled, true);
    ob_start();
    $dirty = $planCase($record, $entry);
    ob_end_clean();
    check255($dirty === [] && $record->getAttributes() === $filled, 'preserves fields added since audit: '.$entry['name']);
}
check255($total === 17, '17 case fields across six records');

$entry = $payload['entries'][0];
$base = array_fill_keys(array_merge($textFields, $dateFields), null);
$base['date_precision'] = json_encode(['arrest_date' => 'year']);
$record = new App\Models\PrisonerCase;
$record->setRawAttributes($base, true);
ob_start();
$dirty = $planCase($record, $entry);
ob_end_clean();
check255(! isset($dirty['arrest_date']) && $record->date_precision['arrest_date'] === 'year', 'conflicting existing precision is preserved');

$entry = $payload['entries'][1];
foreach ([0, false, ' ', 'No'] as $value) {
    $base = array_fill_keys(array_merge($textFields, $dateFields), null);
    $base['judge'] = $value;
    $record->setRawAttributes($base, true);
    ob_start();
    $dirty = $planCase($record, $entry);
    ob_end_clean();
    check255(! array_key_exists('judge', $dirty), 'does not mistake populated values for empty');
}
$base['judge'] = '';
$record->setRawAttributes($base, true);
$dirty = $planCase($record, $entry);
check255($dirty['judge'] === $entry['fields']['judge'], 'fills an empty string');
echo "B255-PLANNER-TESTS-OK\n";
