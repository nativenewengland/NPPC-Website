<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ImprisonmentDuration
{
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
        $months = 0;
        $counted = 0;

        foreach ($cases as $case) {
            if (! $case->imprisoned_for_days) {
                continue;
            }

            if (! $case->imprisoned_for_months) {
                return null;
            }

            $months += (int) $case->imprisoned_for_months;
            $counted++;
        }

        return $counted > 0 ? $months : null;
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
