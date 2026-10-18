<?php

namespace Tests\Feature;

use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Tests\TestCase;

class PartialCustodyDurationTest extends TestCase
{
    public function test_partial_endpoints_do_not_supply_exact_day_totals(): void
    {
        foreach (['year', 'month', 'circa'] as $precision) {
            foreach (['incarceration_date', 'release_date'] as $field) {
                $case = new PrisonerCase([
                    'incarceration_date' => '1926-06-01',
                    'release_date' => '1926-07-01',
                    'date_precision' => [$field => $precision],
                ]);
                $this->assertNull($case->computeImprisonedForDays());
            }
        }
    }

    public function test_partial_open_custody_does_not_count_placeholder_days_to_today(): void
    {
        $case = new PrisonerCase();
        $case->setPartialDate('incarceration_date', 1926, 6);
        $case->setRelation('prisoner', new Prisoner(['in_custody' => true]));

        $this->assertNull($case->computeImprisonedForDays());
    }

    public function test_documented_months_still_override_uncertain_endpoints(): void
    {
        $case = new PrisonerCase(['imprisoned_for_months' => 2]);
        $case->setPartialDate('incarceration_date', 1926, 6);
        $case->setPartialDate('release_date', 1926);

        $this->assertSame(61, $case->computeImprisonedForDays());
    }

    public function test_legacy_exact_dates_still_supply_their_day_total(): void
    {
        $case = new PrisonerCase([
            'incarceration_date' => '1926-06-01',
            'release_date' => '1926-07-01',
        ]);

        $this->assertSame(30, $case->computeImprisonedForDays());
    }
    public function test_month_only_closed_case_displays_calendar_month_difference(): void
    {
        $case = new PrisonerCase();
        $case->setPartialDate('incarceration_date', 1965, 4);
        $case->setPartialDate('release_date', 1966, 5);

        $this->assertSame('1 Year 1 Month', \App\Support\PartialImprisonmentDuration::phrase([$case], false));
        $this->assertNull($case->computeImprisonedForDays());
    }

    public function test_exact_case_keeps_existing_counter_formatter(): void
    {
        $case = new PrisonerCase([
            'incarceration_date' => '1965-04-12',
            'release_date' => '1966-05-12',
        ]);
        $this->assertNull(\App\Support\PartialImprisonmentDuration::phrase([$case], false));
    }
    public function test_overlapping_month_cases_do_not_double_count(): void
    {
        $a = new PrisonerCase();
        $a->setPartialDate('incarceration_date', 1965, 4);
        $a->setPartialDate('release_date', 1966, 5);
        $b = new PrisonerCase();
        $b->setPartialDate('incarceration_date', 1966, 1);
        $b->setPartialDate('release_date', 1966, 7);
        $this->assertSame('1 Year 3 Months', \App\Support\PartialImprisonmentDuration::phrase([$a, $b], false));
    }
}
