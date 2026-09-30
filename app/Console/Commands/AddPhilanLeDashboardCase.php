<?php

namespace App\Console\Commands;

use App\Models\DashboardLink;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Add Washington Post reporting on Philan-Tam-Duy Le's guillotine protest arrest. */
final class AddPhilanLeDashboardCase extends Command
{
    protected $signature = 'dashboard:add-philan-le-case';

    protected $description = 'Add the Washington Post report on Philan-Tam-Duy Le to the dashboard';

    public function handle(): int
    {
        $url = 'https://www.washingtonpost.com/dc-md-va/2026/09/01/he-brought-guillotine-dc-he-didnt-expect-face-felony/';

        $link = DashboardLink::updateOrCreate(
            ['url' => $url],
            [
                'title' => 'He brought a guillotine to D.C. He didn’t expect to get arrested.',
                'source' => 'The Washington Post',
                'category' => 'arrest',
                'published_at' => Carbon::parse('2026-09-01'),
                'location_label' => 'U.S. Capitol and Supreme Court, Washington, DC',
                'lat' => 38.8900,
                'lng' => -77.0091,
            ],
        );

        $this->info(($link->wasRecentlyCreated ? 'Added' : 'Refreshed')." dashboard link: {$link->title}");

        return self::SUCCESS;
    }
}
