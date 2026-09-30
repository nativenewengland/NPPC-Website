<?php

namespace App\Console\Commands;

use App\Models\DashboardLink;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/** Add Reuters reporting on the federal case against Michigan nurse Chloe Freeman. */
final class AddChloeFreemanDashboardCase extends Command
{
    protected $signature = 'dashboard:add-chloe-freeman-case';

    protected $description = 'Add the Reuters report on the prosecution of Michigan nurse Chloe Freeman';

    public function handle(): int
    {
        $url = 'https://www.reuters.com/legal/litigation/michigan-nurse-charged-with-helping-immigrant-flee-ice-custody-2026-09-29/';

        $link = DashboardLink::updateOrCreate(
            ['url' => $url],
            [
                'title' => 'Michigan nurse charged with helping immigrant flee ICE custody',
                'source' => 'Reuters',
                'category' => 'prosecution',
                'published_at' => Carbon::parse('2026-09-29'),
                'location_label' => 'Trinity Health Ann Arbor Hospital, Ann Arbor, MI',
                'lat' => 42.2631,
                'lng' => -83.6587,
            ],
        );

        $this->info(($link->wasRecentlyCreated ? 'Added' : 'Refreshed')." dashboard link: {$link->title}");

        return self::SUCCESS;
    }
}
