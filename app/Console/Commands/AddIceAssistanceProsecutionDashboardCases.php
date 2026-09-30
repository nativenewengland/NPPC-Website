<?php

namespace App\Console\Commands;

use App\Models\DashboardLink;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Add recent prosecutions and discipline for allegedly helping people evade ICE. */
final class AddIceAssistanceProsecutionDashboardCases extends Command
{
    protected $signature = 'dashboard:add-ice-assistance-prosecutions';

    protected $description = 'Add the Luna–Sanchez-Juarez prosecution and Shelley Joseph disciplinary outcome';

    public function handle(): int
    {
        $cases = [
            [
                'url' => 'https://utahnewsdispatch.com/2026/05/29/utah-immigration-escape/',
                'title' => 'Utah couple accused of helping man flee immigration agents face federal charges',
                'source' => 'Utah News Dispatch',
                'category' => 'prosecution',
                'published_at' => '2026-05-29',
                'location_label' => 'Salt Lake City, UT',
                'lat' => 40.7608,
                'lng' => -111.8910,
            ],
            [
                'url' => 'https://apnews.com/article/ad54a5b6f36c286ebac99da3ce9d9195',
                'title' => 'Judge accused of enabling immigrant to escape ICE gets a rare public reprimand',
                'source' => 'Associated Press',
                'category' => 'prosecution',
                'published_at' => '2026-09-10',
                'location_label' => 'Newton District Court, Newton, MA',
                'lat' => 42.3370,
                'lng' => -71.2092,
            ],
        ];

        foreach ($cases as $case) {
            $url = $case['url'];
            unset($case['url']);
            $case['published_at'] = Carbon::parse($case['published_at']);

            $link = DashboardLink::updateOrCreate(['url' => $url], $case);
            $this->info(($link->wasRecentlyCreated ? 'Added' : 'Refreshed')." dashboard link: {$link->title}");
        }

        return self::SUCCESS;
    }
}
