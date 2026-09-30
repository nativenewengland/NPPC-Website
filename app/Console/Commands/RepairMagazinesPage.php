<?php

namespace App\Console\Commands;

use App\Models\ArchiveRecord;
use App\Models\Page;
use Illuminate\Console\Command;

final class RepairMagazinesPage extends Command {
    protected $signature = 'archive:repair-magazines-page';
    protected $description = 'Restore the Magazines page navigation and its missing featured archive record';

    public function handle(): int {
        $page = Page::where('slug', 'magazines')->first();
        if (! $page) {
            $this->error('The magazines page record was not found.');
            return self::FAILURE;
        }

        $learnMore = Page::where('slug', 'learn-more')
            ->orWhere('title', 'Learn More')
            ->first();
        $archive = Page::where('slug', 'archive')->first();

        $page->parent_id = $learnMore?->id;
        $page->show_in_nav = true;
        $page->sort_order = $archive ? ((int) $archive->sort_order + 1) : (int) $page->sort_order;
        $page->header_image = null;
        $page->save();

        ArchiveRecord::updateOrCreate(
            ['slug' => 'social-anarchism-issue-22-1996'],
            [
                'title' => 'Social Anarchism, Issue #22 (1996)',
                'description' => 'Issue 22 of Social Anarchism, including writing on political prisoners, political trials, labor, race, and anarchist organizing.',
                'record_type' => 'magazine',
                'source_format' => 'periodical',
                'file' => 'https://www.socialanarchism.org/issue/22',
                'thumbnail' => '/images/archive/social-anarchism-22.gif',
                'year' => 1996,
                'date' => '1996-01-01',
                'publisher' => 'Atlantic Center for Research and Education',
                'collection' => 'Social Anarchism',
                'volume' => 'Issue #22',
                'subjects' => ['Anarchism', 'Political Prisoners', 'Political Trials', 'Movement Press'],
                'is_digitized' => true,
                'published' => true,
            ]
        );

        ArchiveRecord::where('slug', 'nuclear-resister-no-208-jul-2026')
            ->update(['thumbnail' => 'archive-thumbnails/nuclear-resister-no-208-jul-2026.jpg']);

        $this->info('Magazines navigation, featured records, and current-issue cover restored.');
        return self::SUCCESS;
    }
}
