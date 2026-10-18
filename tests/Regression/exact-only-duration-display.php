<?php
require getcwd().'/vendor/autoload.php';
$candidate = getenv('NPPC_TEST_SUPPORT_DIR') ?: getcwd().'/app/Support';
foreach (['CustodyPeriods', 'ImprisonmentDuration', 'PartialImprisonmentDuration'] as $class) require_once $candidate.'/'.$class.'.php';
use App\Support\PartialImprisonmentDuration as Duration;
$make = function ($start, $end, $precision, $extra = []) {
    $case = new App\Models\PrisonerCase;
    $case->setRawAttributes(array_merge(['incarceration_date' => $start, 'release_date' => $end, 'date_precision' => json_encode($precision)], $extra));
    return $case;
};
$check = function ($ok, $name) { if (!$ok) throw new RuntimeException($name); };
$a = $make('1965-04-01', '1966-05-01', ['incarceration_date'=>'month','release_date'=>'month']);
$check(Duration::phrase([$a], false)==='1 Year 1 Month', 'Month-only calendar difference');
$b = $make('1966-01-01', '1966-07-01', ['incarceration_date'=>'month','release_date'=>'month']);
$check(Duration::phrase([$a,$b], false)==='1 Year 3 Months', 'Month overlap union');
$c = $make('1965-04-01','1966-05-12',['incarceration_date'=>'month','release_date'=>'day']);
$check(Duration::phrase([$c], false)==='1 Year 1 Month', 'Mixed endpoint precision stays at month granularity');
$y = $make('1903-01-01','1926-06-01',['incarceration_date'=>'year','release_date'=>'day']);
$check(Duration::phrase([$y],false)===null && Duration::shouldSuppressCounter([$y],false), 'No year-based estimated range');
$exact = $make('1965-04-12','1966-05-12',[]);
$check(Duration::phrase([$exact],false)===null && !Duration::shouldSuppressCounter([$exact],false), 'Keep exact formatter');
$check(Duration::shouldSuppressCounter([$exact,$y],false), 'Never display an exact subset as the total');
$documented = $make('1903-01-01','1926-06-01',['incarceration_date'=>'year'],['documented_imprisoned_for_days'=>365]);
$check(!Duration::shouldSuppressCounter([$documented],false), 'Keep documented exact days with partial dates');
$months = $make('1903-01-01','1926-06-01',['incarceration_date'=>'year'],['imprisoned_for_months'=>24]);
$check(!Duration::shouldSuppressCounter([$months],false), 'Keep source-stated months');
echo "PASS 8 exact-only duration display checks\n";
