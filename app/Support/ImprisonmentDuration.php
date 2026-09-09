<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ImprisonmentDuration
{
    /**
     * Count a single prisoner's stored custody durations without counting
     * overlapping dated spans twice. This never writes to the case records.
     *
     * The stored duration remains authoritative: documented months, manual
     * durations and the model's eligibility rules are not recomputed here.
     * An ordinary arrest alone does not locate a custody span. Durations with
     * no incarceration date remain additive; documented months may use the
     * arrest anchor used by PrisonerCase::computeImprisonedForDays().
     *
     * For an imprecise start, only the part shared by EVERY possible placement
     * can be deduplicated. Two short stays known only to the same year must
     * not become one stay just because both dates are stored as January 1.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     * @return array{days: int, start: ?CarbonInterface, months: ?int}
     */
    public static function summarize(iterable $cases): array
    {
        $intervals = [];
        $unplacedDays = 0;
        $unplacedMonths = 0;
        $rawDays = 0;
        $rawMonths = 0;
        $allMonths = true;
        $preciseStarts = true;
        $earliest = null;
        $monthSpans = [];

        foreach ($cases as $case) {
            $days = (int) ($case->imprisoned_for_days ?? 0);
            if ($days <= 0) {
                continue;
            }
            $rawDays += $days;
            $months = (int) ($case->imprisoned_for_months ?? 0);
            $allMonths = $allMonths && $months > 0;
            $rawMonths += max(0, $months);
            $field = $case->incarceration_date ? 'incarceration_date' : ($months > 0 && $case->arrest_date ? 'arrest_date' : null);
            if ($field === null) {
                $unplacedDays += $days;
                $unplacedMonths += max(0, $months);
                continue;
            }

            $start = Carbon::parse($case->{$field})->startOfDay();
            if ($earliest === null || $start->lessThan($earliest)) {
                $earliest = $start->copy();
            }
            [$first, $last] = match ($case->datePrecisionFor($field)) {
                'month' => [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()->startOfDay()],
                'year' => [$start->copy()->startOfYear(), $start->copy()->endOfYear()->startOfDay()],
                'circa' => [$start->copy()->subYear()->startOfYear(), $start->copy()->addYear()->endOfYear()->startOfDay()],
                default => [$start->copy(), $start->copy()],
            };
            $preciseStarts = $preciseStarts && $first->equalTo($last);
            $end = $first->copy()->addDays($days);
            $coreDays = $end->greaterThan($last) ? (int) $last->diffInDays($end) : 0;
            $unplacedDays += $days - $coreDays;
            if ($coreDays > 0) {
                $intervals[] = [$last, $end];
                if ($first->equalTo($last) && $months > 0) {
                    $monthSpans[$last->toDateString().'|'.$end->toDateString()] = $months;
                }
            }
        }

        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($intervals as [$start, $end]) {
            $lastIndex = count($merged) - 1;
            if ($lastIndex < 0 || $start->greaterThan($merged[$lastIndex][1])) {
                $merged[] = [$start, $end];
            } elseif ($end->greaterThan($merged[$lastIndex][1])) {
                $merged[$lastIndex][1] = $end;
            }
        }
        $total = $unplacedDays;
        foreach ($merged as [$start, $end]) {
            $total += (int) $start->diffInDays($end);
        }

        $months = null;
        if ($rawDays > 0 && $allMonths) {
            if ($total === $rawDays) {
                // Preserve source-stated units when no overlap was removed.
                $months = $rawMonths;
            } elseif ($preciseStarts) {
                // Do not restore the inflated raw month sum after deduping.
                // Whole-calendar-month unions can still use month precision.
                $months = $unplacedMonths;
                foreach ($merged as [$start, $end]) {
                    $spanKey = $start->toDateString().'|'.$end->toDateString();
                    if (isset($monthSpans[$spanKey])) {
                        $months += $monthSpans[$spanKey];
                        continue;
                    }
                    $diff = $start->diff($end);
                    if ($diff->d !== 0) {
                        $months = null;
                        break;
                    }
                    $months += $diff->y * 12 + $diff->m;
                }
            }
        }

        return ['days' => $total, 'start' => $earliest, 'months' => $months];
    }

    /** @param iterable<\App\Models\PrisonerCase> $cases Cases for one prisoner. */
    public static function totalDays(iterable $cases): int
    {
        return self::summarize($cases)['days'];
    }

    /** Merge within each prisoner, never between different people. */
    public static function totalDaysAcrossPrisoners(iterable $cases): int
    {
        $groups = [];
        foreach ($cases as $case) {
            $groups[$case->prisoner_id][] = $case;
        }

        return array_sum(array_map(fn ($rows) => self::totalDays($rows), $groups));
    }

    /**
     * Break a span of $days that began on $start into calendar-accurate
     * [years, months, days].
     *
     * Anchoring to the real start date and letting Carbon walk the calendar
     * (instead of dividing by fixed 365-day years and 30-day months) means a
     * span such as May 13 → Dec 13 reads as exactly 7 months, not the
     * "7 months 4 days" you get from 214 ÷ 30. When no start date is known we
     * anchor at today minus the span, so the result is still real calendar
     * units rather than a 30-day-month fiction.
     *
     * @param  CarbonInterface|string|null  $start
     * @return array{years:int,months:int,days:int}
     */
    public static function breakdown($start, int $days): array
    {
        if ($days <= 0) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        $start = $start ? Carbon::parse($start)->startOfDay() : Carbon::today()->subDays($days);
        $diff = $start->diff($start->copy()->addDays($days));

        return ['years' => $diff->y, 'months' => $diff->m, 'days' => $diff->d];
    }

    /**
     * The months to render instead of a day-level span, or null to fall back
     * to breakdown().
     *
     * Returns a figure only when EVERY case that contributes time documents it
     * in months. A record mixing a months-only case with a date-derived one has
     * no honest single unit, so it keeps the day breakdown rather than silently
     * rounding the dated case into months.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     */
    public static function documentedMonths(iterable $cases): ?int
    {
        return self::summarize($cases)['months'];
    }

    /**
     * "3 Years 2 Months", or "3 Years 2 Months 5 Days" — the counter phrase.
     *
     * $months is a duration a source stated in whole months; when it is given,
     * convert each twelve months into a year, retaining month-level precision
     * instead of deriving days from endpoints that cannot support them.
     */
    public static function phrase($start, int $days, ?int $months = null): string
    {
        if ($months !== null && $months > 0) {
            $years = intdiv($months, 12);
            $remainingMonths = $months % 12;
            $parts = [];
            if ($years > 0) {
                $parts[] = $years.' '.($years === 1 ? 'Year' : 'Years');
            }
            if ($remainingMonths > 0) {
                $parts[] = $remainingMonths.' '.($remainingMonths === 1 ? 'Month' : 'Months');
            }

            return implode(' ', $parts);
        }

        ['years' => $y, 'months' => $m, 'days' => $d] = self::breakdown($start, $days);

        $parts = [];
        if ($y > 0) {
            $parts[] = $y.' '.($y === 1 ? 'Year' : 'Years');
        }
        if ($m > 0) {
            $parts[] = $m.' '.($m === 1 ? 'Month' : 'Months');
        }
        $parts[] = $d.' '.($d === 1 ? 'Day' : 'Days');

        return implode(' ', $parts);
    }
}
