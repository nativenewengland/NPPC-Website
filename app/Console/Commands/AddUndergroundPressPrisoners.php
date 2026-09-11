<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

final class AddUndergroundPressPrisoners extends Command
{
    protected $signature = 'prisoners:add-underground-press {--dry-run : Check identities without writing records}';

    protected $description = 'Add four researched underground-press and Columbus antiwar detainees';

    public function handle(): int
    {
        $payload = json_decode(File::get(database_path('data/underground-press-prisoners.json')), true, 512, JSON_THROW_ON_ERROR);
        $dryRun = (bool) $this->option('dry-run');

        $added = DB::transaction(function () use ($payload, $dryRun): int {
            // Include hidden profiles and aliases before creating any records.
            $records = Prisoner::withoutGlobalScopes()->get(['id', 'name', 'aka', 'first_name', 'middle_name', 'last_name', 'slug', 'sort_order']);
            $missing = [];

            foreach ($payload['entries'] as $entry) {
                $names = array_map($this->normalize(...), $entry['match_names']);
                $matches = $records->filter(function (Prisoner $record) use ($names): bool {
                    $haystack = ' '.$this->normalize(implode(' ', [
                        $record->name, $record->aka, $record->first_name,
                        $record->middle_name, $record->last_name, $record->slug,
                    ])).' ';

                    foreach ($names as $name) {
                        $tokens = explode(' ', $name);
                        if (count($tokens) >= 2 && collect($tokens)->every(fn ($token) => str_contains($haystack, ' '.$token.' '))) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($matches->count() > 1) {
                    throw new RuntimeException('Ambiguous identity; no records added: '.$entry['prisoner']['name']);
                }

                if ($matches->isNotEmpty()) {
                    $this->line('Preserved existing: '.$entry['prisoner']['name']);
                } else {
                    $missing[] = $entry['prisoner'];
                }
            }

            // Explicit positions avoid prisoner:add shifting existing curated rows.
            $nextOrder = (int) $records->max('sort_order') + 1;
            foreach ($missing as $person) {
                if ($dryRun) {
                    $this->line('Would add: '.$person['name']);

                    continue;
                }

                $person['sort_order'] = $nextOrder++;
                $status = $this->callSilent('prisoner:add', ['json' => json_encode($person, JSON_THROW_ON_ERROR)]);
                if ($status !== self::SUCCESS) {
                    throw new RuntimeException('Import failed; transaction rolled back: '.$person['name']);
                }
                $this->line('Added: '.$person['name']);
            }

            return count($missing);
        });

        if (! $dryRun && $added > 0) {
            Cache::forget(PrisonerApiController::cacheKey());
            Cache::forget('museum:payload:v2');
            Cache::forget('tracker:payload:v2:'.date('Y'));
        }

        $this->info(($dryRun ? 'Would add profiles: ' : 'Added profiles: ').$added);

        return self::SUCCESS;
    }

    private function normalize(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower(Str::ascii($value))));
    }
}
