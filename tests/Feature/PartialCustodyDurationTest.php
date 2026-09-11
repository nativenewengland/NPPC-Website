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
}
