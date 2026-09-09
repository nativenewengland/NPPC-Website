<?php

// Dates describe counter fixtures, not changes to historical records.
$span = fn ($start, $days, $extra = []) => array_merge([
    'incarceration_date' => $start,
    'imprisoned_for_days' => $days,
], $extra);

return [
    'Dorothy Day duplicate 14-day span' => [
        [$span('1917-11-14', 14), $span('1917-11-14', 14)], 14, null, '1917-11-14',
    ],
    'Fernando Gonzalez duplicate 5647-day span' => [
        [$span('1998-09-12', 5647), $span('1998-09-12', 5647)], 5647, null, '1998-09-12',
    ],
    'partial overlap' => [
        [$span('2020-01-01', 10), $span('2020-01-06', 10)], 15, null, '2020-01-01',
    ],
    'nested spans and duplicate nested row' => [
        [$span('2020-01-04', 2), $span('2020-01-01', 20), $span('2020-01-04', 2)], 20, null, '2020-01-01',
    ],
    'three spans linked by a bridge' => [
        [$span('2020-01-01', 10), $span('2020-01-16', 5), $span('2020-01-08', 10)], 20, null, '2020-01-01',
    ],
    'touching periods and a genuine gap' => [
        [$span('2020-01-01', 5), $span('2020-01-06', 5), $span('2020-02-01', 3)], 13, null, '2020-01-01',
    ],
    'uncounted cases must not anchor the calendar' => [
        [$span('1900-01-01', null), $span('1901-01-01', 0), $span('1902-01-01', -5), $span('2020-01-01', 5)], 5, null, '2020-01-01',
    ],
    'manual duration without an incarceration date' => [
        [$span(null, 3, ['arrest_date' => '2020-01-01']), $span('2020-01-01', 5)], 8, null, '2020-01-01',
    ],
    'stored duration outranks a longer case endpoint' => [
        [$span('2020-01-01', 5, ['release_date' => '2020-12-31']), $span('2020-01-10', 5)], 10, null, '2020-01-01',
    ],
    'open case never extends past its stored duration' => [
        [$span('2020-01-01', 10), $span('2020-01-06', 5)], 10, null, '2020-01-01',
    ],
    'Swann 24 documented months without dates' => [
        [$span(null, 730, ['imprisoned_for_months' => 24])], 730, 24, null,
    ],
    'duplicate documented two-year periods' => [
        [$span('2020-01-01', 731, ['imprisoned_for_months' => 24]), $span('2020-01-01', 731, ['imprisoned_for_months' => 24])], 731, 24, '2020-01-01',
    ],
    'month-end source unit is preserved when deduplicated' => [
        [$span('2021-01-31', 31, ['imprisoned_for_months' => 1]), $span('2021-01-31', 31, ['imprisoned_for_months' => 1])], 31, 1, '2021-01-31',
    ],
    'documented months may use their established arrest anchor' => [
        [$span(null, 31, ['arrest_date' => '2020-01-01', 'imprisoned_for_months' => 1]), $span(null, 31, ['arrest_date' => '2020-01-01', 'imprisoned_for_months' => 1])], 31, 1, '2020-01-01',
    ],
    'two distinct undated month durations remain additive' => [
        [$span(null, 365, ['imprisoned_for_months' => 12]), $span(null, 365, ['imprisoned_for_months' => 12])], 730, 24, null,
    ],
    'mixed units do not restore raw documented months' => [
        [$span('2020-01-01', 31, ['imprisoned_for_months' => 1]), $span('2020-01-10', 10)], 31, null, '2020-01-01',
    ],
    'month precision does not merge two short stays by placeholder' => [
        [$span('2020-01-01', 5, ['date_precision' => '{"incarceration_date":"month"}']), $span('2020-01-01', 5, ['date_precision' => '{"incarceration_date":"month"}'])], 10, null, '2020-01-01',
    ],
    'year precision does not merge short stays by placeholder' => [
        [$span('2020-01-01', 30, ['date_precision' => '{"incarceration_date":"year"}']), $span('2020-01-01', 30, ['date_precision' => '{"incarceration_date":"year"}'])], 60, null, '2020-01-01',
    ],
    'uncertain long stay removes only its guaranteed overlap' => [
        [$span('2020-01-01', 60, ['date_precision' => '{"incarceration_date":"month"}']), $span('2020-02-01', 10)], 60, null, '2020-01-01',
    ],
    'circa dates do not assert a shared exact year' => [
        [$span('2020-01-01', 365, ['date_precision' => '{"incarceration_date":"circa"}']), $span('2020-01-01', 365)], 730, null, '2020-01-01',
    ],
    'empty cases' => [[], 0, null, null],
];
