<?php

namespace App\Support;

use Carbon\Carbon;

/** Completed custody intervals retaining source date precision. */
final class CustodyPeriods
{
    /** ISO year, month or day; never silently invent a day. */
    public static function dateBounds(string $value): ?array
    {
        if (! preg_match('/^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/D', $value, $m)) {
            return null;
        }
        $year = (int) $m[1];
        $month = isset($m[2]) ? (int) $m[2] : 1;
        $day = isset($m[3]) ? (int) $m[3] : 1;
        if (! checkdate($month, $day, $year)) {
            return null;
        }
        $first = Carbon::create($year, $month, $day)->startOfDay();
        $last = match (strlen($value)) {
            4 => $first->copy()->endOfYear()->startOfDay(),
            7 => $first->copy()->endOfMonth()->startOfDay(),
            default => $first->copy(),
        };

        return [$first, $last];
    }

    /** @return list<array{start: string, end: string}>|null */
    public static function normalize(mixed $periods): ?array
    {
        if (! is_array($periods) || ! array_is_list($periods)) {
            return null;
        }

        $intervals = [];
        $partial = false;
        foreach ($periods as $period) {
            if (! is_array($period)) {
                return null;
            }
            foreach (['start', 'end'] as $key) {
                $value = $period[$key] ?? null;
                if (! is_string($value) || self::dateBounds($value) === null) {
                    return null;
                }
                $partial = $partial || strlen($value) !== 10;
            }
            if (self::dateBounds($period['end'])[1]->lt(self::dateBounds($period['start'])[0])) {
                return null;
            }
            $intervals[] = ['start' => $period['start'], 'end' => $period['end']];
        }

        usort($intervals, fn ($a, $b) => $a['start'] <=> $b['start']);
        // Union ambiguous overlaps as bounds, preserving source precision.
        if ($partial) {
            return $intervals;
        }
        $merged = [];
        foreach ($intervals as $period) {
            $last = count($merged) - 1;
            if ($last < 0 || $period['start'] > $merged[$last]['end']) {
                $merged[] = $period;
            } elseif ($period['end'] > $merged[$last]['end']) {
                $merged[$last]['end'] = $period['end'];
            }
        }

        return $merged;
    }

    public static function hasPartial(mixed $periods): bool
    {
        foreach (self::normalize($periods) ?? [] as $period) {
            if (strlen($period['start']) !== 10 || strlen($period['end']) !== 10) {
                return true;
            }
        }

        return false;
    }

    public static function formatDate(string $value): string
    {
        $bounds = self::dateBounds($value);
        if ($bounds === null) {
            return '';
        }

        return $bounds[0]->format(match (strlen($value)) {
            4 => 'Y',
            7 => 'F Y',
            default => 'F j, Y',
        });
    }

    public static function days(mixed $periods): ?int
    {
        $intervals = self::normalize($periods);
        if ($intervals === null || self::hasPartial($intervals)) {
            return null;
        }

        return array_sum(array_map(
            fn ($p) => (int) Carbon::parse($p['start'])->diffInDays(Carbon::parse($p['end'])),
            $intervals,
        ));
    }
}
