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
     * Existing stored durations remain authoritative. A missing duration with
     * a closed custody interval is calculated using the model's rules, so a
     * direct date import does not require an overnight job to show a counter.
     * An ordinary arrest alone does not locate a custody span. Durations with
     * no incarceration date remain additive; documented months may use the
     * arrest anchor used by PrisonerCase::computeImprisonedForDays().
     *
     * For an imprecise start, only the part shared by EVERY possible placement
     * can be deduplicated. Two short stays known only to the same year must
     * not become one stay just because both dates are stored as January 1.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     * @return array{days: int, start: ?CarbonInterface, months: ?int, components: ?array{months: int, days: int}}
     */
    public static function summarize(iterable $cases): array
    {
        $intervals = [];
        $unplacedDays = 0;
        $unplacedMonths = 0;
        $rawDays = 0;
        $rawMonths = 0;
        $monthDays = 0;
        $allMonths = true;
        $preciseStarts = true;
        $monthSpans = [];

        foreach ($cases as $case) {
            if ($case->custody_periods !== null) {
                // Partial endpoints belong to the bounded display, not exact statistics.
                if (CustodyPeriods::hasPartial($case->custody_periods)) {
                    continue;
                }
                foreach (CustodyPeriods::normalize($case->custody_periods) ?? [] as $period) {
                    $start = Carbon::parse($period['start'])->startOfDay();
                    $end = Carbon::parse($period['end'])->startOfDay();
                    $days = (int) $start->diffInDays($end);
                    if ($days > 0) {
                        $intervals[] = [$start, $end];
                        $rawDays += $days;
                        $allMonths = false;
                    }
                }
                continue;
            }

            $days = $case->imprisoned_for_days;
            if ($days === null && (
                $case->documented_imprisoned_for_days !== null
                || $case->imprisoned_for_months > 0
                || ($case->incarceration_date && ($case->release_date || $case->death_in_custody_date))
            )) {
                $days = $case->computeImprisonedForDays();
            }
            $days = (int) ($days ?? 0);
            $months = (int) ($case->imprisoned_for_months ?? 0);
            if ($days <= 0) {
                // A source-stated month count has no exact day equivalent.
                // Retain its units without inventing dates or day totals.
                if ($months > 0) {
                    $rawMonths += $months;
                    $unplacedMonths += $months;
                }
                continue;
            }
            $rawDays += $days;
            $allMonths = $allMonths && $months > 0;
            $rawMonths += max(0, $months);
            if ($months > 0) {
                $monthDays += $days;
            }
            $startField = $case->incarceration_date ? 'incarceration_date' : 'arrest_date';
            $endField = $case->death_in_custody_date ? 'death_in_custody_date' : 'release_date';
            $dateDerived = $case->documented_imprisoned_for_days === null
                && $months === 0
                && $case->{$startField}
                && ($startField === 'arrest_date' || $case->datePrecisionFor($startField) !== 'day'
                    || ($case->{$endField} && $case->datePrecisionFor($endField) !== 'day'));
            $field = $case->incarceration_date
                ? 'incarceration_date'
                : (($months > 0 || $dateDerived) && $case->arrest_date ? 'arrest_date' : null);
            if ($field === null) {
                $unplacedDays += $days;
                $unplacedMonths += max(0, $months);
                continue;
            }

            $start = $dateDerived
                ? PartialImprisonmentDuration::assumedDate($case, $field)
                : Carbon::parse($case->{$field})->startOfDay();
            [$first, $last] = $dateDerived
                ? [$start->copy(), $start->copy()]
                : match ($case->datePrecisionFor($field)) {
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
        if (($rawDays > 0 || $rawMonths > 0) && $allMonths) {
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

        // Calendar units require one continuous, precisely placed span.
        // Unknown starts and separate stays must not borrow a placeholder
        // date or move days across a gap merely to produce months and years.
        $calendarStart = $preciseStarts && $unplacedDays === 0 && count($merged) === 1
            ? $merged[0][0]->copy()
            : null;

        // A month-only report cannot supply an exact day count for a mixed
        // total. Preserve its units when no overlapping time was removed.
        $components = $rawMonths > 0 && ! $allMonths && $total === $rawDays
            ? ['months' => $rawMonths, 'days' => $rawDays - $monthDays]
            : null;

        return ['days' => $total, 'start' => $calendarStart, 'months' => $months, 'components' => $components];
    }

    public static function mixedPhrase(array $summary): ?string
    {
        $parts = $summary['components'] ?? null;
        if ($parts === null) {
            return null;
        }

        return self::phrase(null, 0, $parts['months']).' + '.self::phrase(null, $parts['days']);
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
     * "7 months 4 days" you get from 214 ÷ 30. Without a known start, use
     * conventional 365-day years and average Gregorian months for the
     * remainder at every duration. Separate custody periods cannot share
     * one calendar anchor, but their combined time still uses years, months
     * and days in the public display. The stored day total is unchanged.
     *
     * @param  CarbonInterface|string|null  $start
     * @return array{years:int,months:int,days:int}
     */
    public static function breakdown($start, int $days): array
    {
        if ($days <= 0) {
            return ['years' => 0, 'months' => 0, 'days' => 0];
        }

        if (! $start) {
            $years = intdiv($days, 365);
            $remainder = $days % 365;
            $months = min(11, (int) floor($remainder / 30.436875));
            $remainingDays = (int) round($remainder - ($months * 30.436875));

            return ['years' => $years, 'months' => $months, 'days' => $remainingDays];
        }

        $start = Carbon::parse($start)->startOfDay();
        $diff = $start->diff($start->copy()->addDays($days));

        return ['years' => $diff->y, 'months' => $diff->m, 'days' => $diff->d];
    }

    /**
     * The months to render instead of a day-level span, or null to fall back
     * to breakdown().
     *
     * Returns a figure only when EVERY case that contributes time documents it
     * in months. For mixed records, mixedPhrase() preserves the separate source
     * units instead of converting the month-only contribution to exact days.
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
        if ($d > 0 || $parts === []) {
            $parts[] = $d.' '.($d === 1 ? 'Day' : 'Days');
        }

        return implode(' ', $parts);
    }
}
