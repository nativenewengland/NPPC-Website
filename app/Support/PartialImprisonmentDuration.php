<?php

namespace App\Support;

use Carbon\Carbon;

/** Research bounds and explicitly approximate display durations. */
final class PartialImprisonmentDuration
{
    /**
     * Research bounds alongside the existing exact-day aggregation.
     * Narrow intervals establish the minimum, wide intervals the maximum.
     * Union both sets independently so duplicate cases and release gaps are
     * handled without counting a placeholder January 1 as a real arrest day.
     *
     * @param iterable<\App\Models\PrisonerCase> $cases
     * @return array{min: int, max: int}|null
     */
    public static function bounds(iterable $cases, bool $stillDetained): ?array
    {
        $minimum = [];
        $maximum = [];
        $unplacedDays = 0;
        $hasPartial = false;
        $today = Carbon::today();

        foreach ($cases as $case) {
            if ($case->custody_periods !== null) {
                foreach (CustodyPeriods::normalize($case->custody_periods) ?? [] as $period) {
                    [$firstStart, $lastStart] = CustodyPeriods::dateBounds($period['start']);
                    [$firstEnd, $lastEnd] = CustodyPeriods::dateBounds($period['end']);
                    $firstEnd = $firstEnd->min($today);
                    $lastEnd = $lastEnd->min($today);
                    $hasPartial = $hasPartial || strlen($period['start']) !== 10 || strlen($period['end']) !== 10;
                    if ($lastEnd->greaterThan($firstStart)) {
                        $maximum[] = [$firstStart, $lastEnd];
                    }
                    if ($firstEnd->greaterThan($lastStart)) {
                        $minimum[] = [$lastStart, $firstEnd];
                    }
                }
                continue;
            }
            $days = (int) ($case->imprisoned_for_days ?? 0);
            // Source-stated terms keep their existing formatter. A mixture
            // with partial endpoints cannot promise a finite exact range.
            if ($case->imprisoned_for_months > 0) {
                return null;
            }
            if (! $case->incarceration_date) {
                $unplacedDays += max(0, $days);
                continue;
            }
            [$firstStart, $lastStart] = self::dateBounds($case, 'incarceration_date');
            if ($case->documented_imprisoned_for_days !== null || $days > 0) {
                // Exact stored spans retain the existing duration semantics.
                if (! $firstStart->equalTo($lastStart)) {
                    return null;
                }
                $end = $firstStart->copy()->addDays($days);
                if ($days > 0) {
                    $minimum[] = [$firstStart, $end];
                    $maximum[] = [$firstStart, $end];
                }
                continue;
            }
            $endField = $case->death_in_custody_date ? 'death_in_custody_date' : 'release_date';
            if ($case->{$endField}) {
                [$firstEnd, $lastEnd] = self::dateBounds($case, $endField);
            } elseif ($stillDetained) {
                $firstEnd = $lastEnd = $today;
            } else {
                continue;
            }
            $firstEnd = $firstEnd->min($today);
            $lastEnd = $lastEnd->min($today);
            if ($lastEnd->lessThanOrEqualTo($firstStart)) {
                continue;
            }
            $hasPartial = $hasPartial || ! $firstStart->equalTo($lastStart) || ! $firstEnd->equalTo($lastEnd);
            $maximum[] = [$firstStart, $lastEnd];
            if ($firstEnd->greaterThan($lastStart)) {
                $minimum[] = [$lastStart, $firstEnd];
            }
        }

        return $hasPartial ? [
            'min' => self::unionDays($minimum) + $unplacedDays,
            'max' => self::unionDays($maximum) + $unplacedDays,
        ] : null;
    }

    public static function phrase(iterable $cases, bool $stillDetained): ?string
    {
        $cases = is_array($cases) ? $cases : iterator_to_array($cases);
        $hasPartialPeriods = false;
        foreach ($cases as $case) {
            $hasPartialPeriods = $hasPartialPeriods || CustodyPeriods::hasPartial($case->custody_periods);
        }
        if ($hasPartialPeriods) {
            $bounds = self::bounds($cases, $stillDetained);
            if ($bounds !== null) {
                return 'Estimated '.$bounds['min'].'–'.$bounds['max'].' days';
            }
            // Other unplaced, source-stated terms may prevent a finite
            // combined range. Never show only an exact subset as the total.
            return 'Duration uncertain (partial custody dates)';
        }
        $summary = self::estimate($cases, $stillDetained);

        return $summary === null ? null : (
            ImprisonmentDuration::mixedPhrase($summary)
            ?? ImprisonmentDuration::phrase($summary['start'], $summary['days'], $summary['months'])
        );
    }

    /** Partial dates cannot establish an exact display duration. */
    public static function estimate(iterable $cases, bool $stillDetained): ?array
    {
        // Keep this entry point for the page and API callers. Exact dates,
        // documented day counts and source-stated months are handled by
        // ImprisonmentDuration without choosing a day inside a research window.
        return null;
    }

    /** Resolve the display assumption for a sourced partial date. */
    public static function assumedDate($case, string $field): Carbon
    {
        $date = Carbon::parse($case->{$field})->startOfDay();
        $date = match ($case->datePrecisionFor($field)) {
            'month' => $date->startOfMonth(),
            'year', 'circa' => $date->startOfYear(),
            default => $date,
        };
        [$first, $last] = self::dateBounds($case, $field);

        return $date->max($first)->min($last);
    }

    private static function dateBounds($case, string $field): array
    {
        $date = Carbon::parse($case->{$field})->startOfDay();

        $precision = $case->datePrecisionFor($field);
        $bounds = match ($precision) {
            'month' => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()->startOfDay()],
            'year' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()->startOfDay()],
            'circa' => [$date->copy()->subYear()->startOfYear(), $date->copy()->addYear()->endOfYear()->startOfDay()],
            default => [$date->copy(), $date->copy()],
        };

        // Court records can narrow a partial date to a few days without
        // establishing an exact date (e.g. an order and a later admission).
        // Ignore stale/invalid bounds, including after an exact date edit.
        $window = $case->date_precision['bounds'][$field] ?? null;
        if ($precision !== 'day' && is_array($window)) {
            try {
                $first = Carbon::createFromFormat('!Y-m-d', $window['earliest'] ?? '');
                $last = Carbon::createFromFormat('!Y-m-d', $window['latest'] ?? '');
                if ($first && $last
                    && $first->format('Y-m-d') === ($window['earliest'] ?? null)
                    && $last->format('Y-m-d') === ($window['latest'] ?? null)
                    && $first->betweenIncluded($bounds[0], $bounds[1])
                    && $last->betweenIncluded($first, $bounds[1])) {
                    return [$first, $last];
                }
            } catch (\Throwable) {
                // Fall back to the recorded calendar precision.
            }
        }

        return $bounds;
    }

    private static function unionDays(array $intervals): int
    {
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last < 0 || $start->greaterThan($merged[$last][1])) {
                $merged[] = [$start, $end];
            } elseif ($end->greaterThan($merged[$last][1])) {
                $merged[$last][1] = $end;
            }
        }

        return array_sum(array_map(fn ($span) => (int) $span[0]->diffInDays($span[1]), $merged));
    }
}
