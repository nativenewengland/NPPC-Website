<?php

namespace Tests\Unit;

use App\Models\PrisonerCase;
use App\Support\ImprisonmentDuration;
use PHPUnit\Framework\TestCase;

class ImprisonmentOverlapTest extends TestCase
{
    public static function cases(): array
    {
        return require __DIR__.'/../Fixtures/custody-overlap-cases.php';
    }

    private function rows(array $attributes): array
    {
        return array_map(function ($row) {
            $case = new PrisonerCase;
            $case->setDateFormat('Y-m-d H:i:s');
            $case->setRawAttributes($row);

            return $case;
        }, $attributes);
    }

    /** @dataProvider cases */
    public function test_counts_overlap_without_changing_cases(array $attributes, int $days, ?int $months, ?string $start): void
    {
        $cases = $this->rows($attributes);
        $before = array_map(fn ($c) => $c->getAttributes(), $cases);
        foreach ([$cases, array_reverse($cases)] as $ordered) {
            $summary = ImprisonmentDuration::summarize($ordered);
            $this->assertSame($days, $summary['days']);
            $this->assertSame($months, $summary['months']);
            $this->assertSame($start, $summary['start']?->toDateString());
        }
        $this->assertSame($before, array_map(fn ($c) => $c->getAttributes(), $cases));
    }

    public function test_global_total_never_merges_different_prisoners(): void
    {
        $cases = $this->rows([
            ['prisoner_id' => 'a', 'incarceration_date' => '2020-01-01', 'imprisoned_for_days' => 10],
            ['prisoner_id' => 'a', 'incarceration_date' => '2020-01-01', 'imprisoned_for_days' => 10],
            ['prisoner_id' => 'b', 'incarceration_date' => '2020-01-01', 'imprisoned_for_days' => 10],
        ]);
        $this->assertSame(20, ImprisonmentDuration::totalDaysAcrossPrisoners($cases));
    }
}
