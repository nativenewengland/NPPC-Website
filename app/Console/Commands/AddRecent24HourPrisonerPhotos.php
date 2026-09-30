<?php

namespace App\Console\Commands;

use App\Models\Prisoner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

final class AddRecent24HourPrisonerPhotos extends Command
{
    protected $signature = 'prisoners:add-recent-24h-photos';

    protected $description = 'Attach verified photographs to recently added prisoner profiles that have no image';

    public function handle(): int
    {
        $slugs = [
            'carol-barner-seay',
            'carter-miles-ledoux',
            'dale-britt-bendler',
            'delia-webster',
            'elpidio-reyna',
            'emmarene-kaigler-streeter',
            'jose-manuel-mojica',
            'loay-abdel-fattah-alnaji',
            'lulu-westbrooks-griffin',
            'michael-ron-david-kadar',
            'naomi-destiny-isaac',
            'shirley-green-reese',
            'verna-hollis',
        ];

        Storage::disk('public')->makeDirectory('prisoners');

        $linked = 0;
        foreach ($slugs as $slug) {
            $source = database_path("data/photos/recent-24h-2026-09-30/{$slug}.jpg");
            if (! is_file($source)) {
                $this->warn("Source image missing: {$slug}");

                continue;
            }

            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                $this->warn("Profile not found: {$slug}");

                continue;
            }

            if (! empty($prisoner->photo)) {
                $this->line("Already has a photo: {$prisoner->name}");

                continue;
            }

            $relative = "prisoners/{$slug}.jpg";
            Storage::disk('public')->put($relative, (string) file_get_contents($source));

            $prisoner->photo = $relative;
            $prisoner->save();
            $this->info("Added photo: {$prisoner->name}");
            $linked++;
        }

        $this->info("Done. Linked {$linked} photos.");

        return self::SUCCESS;
    }
}

