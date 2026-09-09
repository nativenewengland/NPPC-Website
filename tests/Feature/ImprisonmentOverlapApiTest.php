<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\ImprisonmentDuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImprisonmentOverlapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_and_profile_summary_count_duplicate_dates_once_and_preserve_case_rows(): void
    {
        $prisoner = Prisoner::create(['name' => 'Overlap Fixture', 'released' => true]);
        foreach ([1, 2] as $number) {
            PrisonerCase::create([
                'prisoner_id' => $prisoner->id,
                'charges' => 'Separate case '.$number,
                'incarceration_date' => '1917-11-14',
                'release_date' => '1917-11-28',
            ]);
        }
        $cases = $prisoner->refresh()->cases;
        $before = $cases->map->getAttributes()->all();
        $summary = ImprisonmentDuration::summarize($cases);
        $this->assertSame(14, $summary['days']);
        $this->assertSame('14 Days', ImprisonmentDuration::phrase($summary['start'], $summary['days'], $summary['months']));

        $payload = app(PrisonerApiController::class)->index(Request::create('/api/prisoners'))->getData(true);
        $record = collect($payload)->firstWhere('id', $prisoner->id);
        $this->assertSame('Imprisoned For 14 days', $record['calculatedPunishment']);
        $this->assertCount(2, $prisoner->fresh()->cases);
        $this->assertSame($before, $prisoner->fresh()->cases->map->getAttributes()->all());
    }
}
