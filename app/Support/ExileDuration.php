<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Total exile time for a single prisoner, as the union of their cases'
 * exile intervals.
 *
 * Case rows can describe overlapping periods of exile or imprisonment.
 * ImprisonmentDuration handles custody overlaps separately.
 * An open-ended exile row counts through to *today*, so two rows describing
 * the same ongoing exile each contribute the whole span from their own start
 * to the present, and the overlap is added twice.
 *
 * William Morales is what that looks like in public: his Bellevue-escape row
 * (in exile since 1979-05-21) and his Mexican-custody row (in exile since
 * 1988-06-24) were summed into "Time in Exile: 85 Years 3 Months 21 Days" —
 * 47 years plus 38 years — for a man who has lived in Cuba since 1988.
 *
 * Merging the intervals keeps genuinely separate exiles additive (a return
 * home and a later second departure are two disjoint spans and still add up)
 * while counting any shared stretch once.
 */
final class ExileDuration
{
    /**
     * Total days in exile across $cases, overlapping spans counted once.
     *
     * Each case's stored in_exile_for_days decides both whether it counts and
     * how long its span runs, so every rule in
     * PrisonerCase::computeInExileForDays() still holds: a case with no
     * in_exile_since, an exile that ends before it begins, and an open-ended
     * exile belonging to somebody not flagged currently_in_exile all store
     * null and are skipped here.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     */
    public static function totalDays(iterable $cases): int
    {
        $intervals = self::intervals($cases);

        if (! $intervals) {
            return 0;
        }

        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

        $total = 0;
        [$openStart, $openEnd] = array_shift($intervals);

        foreach ($intervals as [$start, $end]) {
            if ($start > $openEnd) {
                // A clear gap: the previous exile ended before this one began,
                // so both count in full.
                $total += (int) $openStart->diffInDays($openEnd);
                [$openStart, $openEnd] = [$start, $end];

                continue;
            }

            // Overlapping or touching — extend the run rather than add it.
            if ($end > $openEnd) {
                $openEnd = $end;
            }
        }

        return $total + (int) $openStart->diffInDays($openEnd);
    }

    /**
     * The start of the earliest counted exile, for anchoring the calendar
     * breakdown in ImprisonmentDuration::breakdown().
     *
     * Deliberately not just "the earliest in_exile_since": a case whose exile
     * days came back null contributes nothing to the total, so anchoring the
     * breakdown at its start would measure the span from a date the total does
     * not include.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     */
    public static function startFor(iterable $cases): ?CarbonInterface
    {
        $starts = array_column(self::intervals($cases), 0);

        return $starts ? min($starts) : null;
    }

    /**
     * The [start, end] Carbon pairs that actually count, one per case.
     *
     * @param  iterable<\App\Models\PrisonerCase>  $cases
     * @return array<int, array{0: CarbonInterface, 1: CarbonInterface}>
     */
    private static function intervals(iterable $cases): array
    {
        $intervals = [];

        foreach ($cases as $case) {
            $days = $case->in_exile_for_days;

            if ($days === null || $days <= 0 || ! $case->in_exile_since) {
                continue;
            }

            $start = Carbon::parse($case->in_exile_since)->startOfDay();
            $intervals[] = [$start, $start->copy()->addDays((int) $days)];
        }

        return $intervals;
    }
}
