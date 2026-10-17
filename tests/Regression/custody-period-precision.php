<?php

// Run from the application root: php tests/Regression/custody-period-precision.php
require getcwd().'/vendor/autoload.php';
$candidate = getenv('NPPC_TEST_SUPPORT_DIR') ?: getcwd().'/app/Support';
foreach (['CustodyPeriods', 'ImprisonmentDuration', 'PartialImprisonmentDuration'] as $class) {
    require_once $candidate.'/'.$class.'.php';
}

use App\Support\CustodyPeriods;
use App\Support\ImprisonmentDuration;
use App\Support\PartialImprisonmentDuration;

$checks = 0;
$check = function ($condition, $message) use (&$checks) {
    if (! $condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$case = fn ($periods) => (object) ['custody_periods' => $periods];
$periods = [
    ['start' => '1917-09', 'end' => '1918-03'],
    ['start' => '1918-09', 'end' => '1922-05-14'],
];
$check(CustodyPeriods::normalize($periods) === $periods, 'Preserve partial endpoint precision');
$check(CustodyPeriods::days($periods) === null, 'No invented exact day count');
$check(ImprisonmentDuration::summarize([$case($periods)])['days'] === 0, 'No partial time in exact statistics');
$check(CustodyPeriods::formatDate('1917-09') === 'September 1917', 'Month label');
$check(CustodyPeriods::formatDate('1917') === '1917', 'Year label');
$check(CustodyPeriods::formatDate('1922-05-14') === 'May 14, 1922', 'Exact date label');
foreach (['1917-00', '1917-13', '1917-02-29', '0000', '1917-9', '1917-09-01garbage'] as $bad) {
    $check(CustodyPeriods::normalize([['start' => $bad, 'end' => '1922']]) === null, 'Reject invalid endpoint '.$bad);
}
$check(CustodyPeriods::normalize([['start' => '1922', 'end' => '1917']]) === null, 'Reject reversed periods');
$bounds = PartialImprisonmentDuration::bounds([$case($periods)], false);
$days = fn ($a, $b) => (new DateTimeImmutable($a))->diff(new DateTimeImmutable($b))->days;
$min = $days('1917-09-30', '1918-03-01') + $days('1918-09-30', '1922-05-14');
$max = $days('1917-09-01', '1918-03-31') + $days('1918-09-01', '1922-05-14');
$check($bounds === ['min' => $min, 'max' => $max], 'Exclude gap from both uncertainty bounds');
$check(PartialImprisonmentDuration::phrase([$case($periods)], false) === 'Estimated '.$min.'–'.$max.' days', 'Released profile displays qualified range');
$check(PartialImprisonmentDuration::bounds([$case($periods), $case($periods)], false) === $bounds, 'Duplicate intervals not counted twice');
$years = [['start' => '1917', 'end' => '1918']];
$check(CustodyPeriods::normalize($years) === $years, 'Accept year precision');
$check(PartialImprisonmentDuration::bounds([$case($years)], false) === ['min' => $days('1917-12-31', '1918-01-01'), 'max' => $days('1917-01-01', '1918-12-31')], 'Year uncertainty bounds');
$exact = [['start' => '2000-01-01', 'end' => '2000-01-10'], ['start' => '2000-01-05', 'end' => '2000-01-15']];
$check(CustodyPeriods::days($exact) === 14, 'Exact overlap regression');
$check(ImprisonmentDuration::summarize([$case($exact)])['days'] === 14, 'Exact counter regression');
$check(PartialImprisonmentDuration::phrase([$case($exact)], false) === null, 'Exact intervals keep normal formatter');
$mixed = [$case($periods), $case([['start' => '2000-01-01', 'end' => '2000-01-11']])];
$check(PartialImprisonmentDuration::bounds($mixed, false) === ['min' => $min + 10, 'max' => $max + 10], 'Include exact stays in mixed range');
$unplaced = (object) ['custody_periods' => null, 'imprisoned_for_days' => null, 'imprisoned_for_months' => 3];
$check(PartialImprisonmentDuration::phrase([$case($periods), $unplaced], false) === 'Duration uncertain (partial custody dates)', 'Unplaced terms cannot expose an exact subset as total');
$model = new App\Models\PrisonerCase;
$model->setRawAttributes(['custody_periods' => json_encode($periods)]);
$check($model->computeImprisonedForDays() === null, 'Actual case model does not persist placeholder day totals');
$model->setRawAttributes(['custody_periods' => json_encode($exact)]);
$check($model->computeImprisonedForDays() === 14, 'Actual case model exact behavior preserved');
echo 'PASS '.$checks.' custody precision checks; Mahler range '.$min.'–'.$max." days\n";
