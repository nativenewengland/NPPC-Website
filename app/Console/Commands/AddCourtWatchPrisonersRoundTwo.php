<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AddCourtWatchPrisonersRoundTwo extends Command
{
    protected $signature = 'prisoners:add-court-watch-prisoners-round-two';

    protected $description = 'Add the eight qualifying prisoners found in the second Court Watch archive audit';

    public function handle(): int
    {
        $path = database_path('data/audits/courtwatch-seamus-hughes-2026-09-29/round-two-profiles.json');
        $people = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (count($people) !== 8) {
            throw new RuntimeException('Expected exactly eight second-pass Court Watch profiles.');
        }

        DB::transaction(function () use ($people): void {
            foreach ($people as $person) {
                $prisoner = Prisoner::withUnderReview()->where('slug', $person['slug'])->first()
                    ?? new Prisoner(['name' => $person['name'], 'slug' => $person['slug']]);
                $existing = $prisoner->exists;

                $prisoner->fill([
                    'name' => $person['name'],
                    'first_name' => $person['first'],
                    'middle_name' => $person['middle'] ?? null,
                    'last_name' => $person['last'],
                    'gender' => $person['gender'] ?? 'Male',
                    'state' => $person['state'],
                    'era' => '2020s',
                    'ideologies' => $person['ideologies'],
                    'affiliation' => $person['affiliation'] ?? [],
                    'description' => $person['bio'],
                    'body' => $this->sourceBody($person['sources']),
                    'in_custody' => $person['status'] === 'custody',
                    'released' => $person['status'] === 'released',
                    'in_exile' => false,
                    'currently_in_exile' => false,
                    'awaiting_trial' => $person['awaiting_trial'] ?? false,
                    'under_review' => false,
                    'minor_case' => false,
                ]);
                $prisoner->save();

                $prisoner->cases()->delete();
                $case = $person['case'];
                PrisonerCase::create([
                    'prisoner_id' => $prisoner->id,
                    'charges' => $case['charges'],
                    'indicted' => $case['indicted'] ?? null,
                    'plead' => $case['plead'] ?? null,
                    'convicted' => $case['convicted'] ?? null,
                    'sentence' => $case['sentence'] ?? null,
                    'arrest_date' => $case['arrest_date'] ?? null,
                    'sentenced_date' => $case['sentenced_date'] ?? null,
                    'incarceration_date' => $case['incarceration_date'],
                    'release_date' => $case['release_date'] ?? null,
                    'documented_imprisoned_for_days' => $case['documented_days'] ?? null,
                    'imprisoned_for_months' => $case['documented_months'] ?? null,
                    'date_precision' => $this->dayPrecision($case),
                ]);

                $this->info(($existing ? 'Updated: ' : 'Added: ').$prisoner->name.' ('.$prisoner->slug.')');
            }
        });

        Cache::forget(PrisonerApiController::cacheKey());

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $case */
    private function dayPrecision(array $case): array
    {
        $precision = [];
        foreach (['arrest_date', 'sentenced_date', 'incarceration_date', 'release_date'] as $field) {
            if (! empty($case[$field])) {
                $precision[$field] = 'day';
            }
        }

        return $precision;
    }

    /** @param array<int, array{0: string, 1: string}> $sources */
    private function sourceBody(array $sources): string
    {
        $items = array_map(
            fn (array $source): string => '<li><a href="'.e($source[1]).'" target="_blank" rel="noopener noreferrer">'.e($source[0]).'</a></li>',
            $sources
        );

        return '<h2>Sources</h2><ul>'.implode('', $items).'</ul>';
    }
}
