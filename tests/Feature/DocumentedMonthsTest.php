<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\ImprisonmentDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * A time served the source states in months outranks the date arithmetic.
 *
 * Bill Sutherland is the case: every surviving summary agrees on 38 months of
 * a four-year sentence, while they disagree about the years (1942-45, 1943-45,
 * 1943-46) and no prison register fixes a day. The counter must read
 * "3 Years 2 Months" rather than a day-level span derived from endpoints that cannot
 * support one.
 */
class DocumentedMonthsTest extends TestCase
{
    use RefreshDatabase;

    public function test_documented_months_drive_the_stored_day_count(): void
    {
        $prisoner = Prisoner::create(['name' => 'Months Documented', 'released' => true]);

        $case = PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'incarceration_date' => '1942-07-01',
            'release_date' => '1945-01-01',
            'imprisoned_for_months' => 38,
        ]);

        // Walked forward from the start — July 1942 + 38 months — rather than
        // measured to the release date, which the sources do not pin down.
        $this->assertSame(1158, $case->refresh()->imprisoned_for_days);
    }

    public function test_counter_reads_the_months(): void
    {
        $prisoner = Prisoner::create(['name' => 'Months Shown', 'released' => true]);

        PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'incarceration_date' => '1942-07-01',
            'imprisoned_for_months' => 38,
        ]);

        $cases = $prisoner->refresh()->cases;
        $months = ImprisonmentDuration::documentedMonths($cases);

        $this->assertSame(38, $months);
        $this->assertSame('3 Years 2 Months', ImprisonmentDuration::phrase(
            '1942-07-01', (int) $cases->sum('imprisoned_for_days'), $months,
        ));
    }

    public function test_a_record_without_documented_months_keeps_the_day_breakdown(): void
    {
        $prisoner = Prisoner::create(['name' => 'Days Derived', 'released' => true]);

        PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'incarceration_date' => '1942-07-01',
            'release_date' => '1942-08-01',
        ]);

        $cases = $prisoner->refresh()->cases;

        $this->assertNull(ImprisonmentDuration::documentedMonths($cases));
        $this->assertSame('1 Month 0 Days', ImprisonmentDuration::phrase(
            '1942-07-01', (int) $cases->sum('imprisoned_for_days'), null,
        ));
    }

    public function test_mixing_a_documented_case_with_a_dated_one_keeps_days(): void
    {
        $prisoner = Prisoner::create(['name' => 'Mixed Units', 'released' => true]);

        PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'incarceration_date' => '1942-07-01',
            'imprisoned_for_months' => 38,
        ]);
        PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'incarceration_date' => '1950-01-01',
            'release_date' => '1950-01-15',
        ]);

        // No honest single unit — rounding the dated case into months would
        // invent precision, so the record keeps the day breakdown.
        $this->assertNull(ImprisonmentDuration::documentedMonths($prisoner->refresh()->cases));
    }

    public function test_singular_month_is_not_pluralised(): void
    {
        $this->assertSame('1 Month', ImprisonmentDuration::phrase('1942-07-01', 30, 1));
    }

    public function test_whole_years_and_remaining_months_do_not_invent_days(): void
    {
        foreach ([11 => '11 Months', 12 => '1 Year', 13 => '1 Year 1 Month', 24 => '2 Years', 25 => '2 Years 1 Month'] as $months => $expected) {
            $this->assertSame($expected, ImprisonmentDuration::phrase(null, 0, $months));
        }
    }

    public function test_swanns_documented_two_years_match_in_profile_and_api_without_changing_case_data(): void
    {
        $prisoner = Prisoner::create(['name' => 'Robert Swann', 'released' => true]);
        $case = PrisonerCase::create([
            'prisoner_id' => $prisoner->id,
            'imprisoned_for_months' => 24,
        ]);
        $before = $case->refresh()->getAttributes();
        $cases = $prisoner->refresh()->cases;

        $this->assertSame('2 Years', ImprisonmentDuration::phrase(
            null, (int) $cases->sum('imprisoned_for_days'), ImprisonmentDuration::documentedMonths($cases),
        ));
        $payload = app(PrisonerApiController::class)->index(Request::create('/api/prisoners'))->getData(true);
        $record = collect($payload)->firstWhere('id', $prisoner->id);
        $this->assertSame('Imprisoned For 2 years', $record['calculatedPunishment']);
        $this->assertSame(24, $record['imprisonedForMonths']);
        $this->assertSame($before, $case->refresh()->getAttributes());
    }
}
