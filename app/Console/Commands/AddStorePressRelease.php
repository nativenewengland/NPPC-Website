<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Publishes the press release announcing the launch of the NPPC store,
 * dated November 20, 2024 per editorial direction. Keyed by slug,
 * idempotent (re-runs update in place).
 */
final class AddStorePressRelease extends Command {
    protected $signature = 'articles:add-store-press-release';
    protected $description = 'Publish the NPPC store launch press release';

    public function handle(): int {
        // Keyed on the SLUG, not the title — see AddMobileAppPressRelease.
        $category = Category::firstOrCreate(['slug' => 'press-releases'], ['title' => 'Press Releases']);
        $author   = Author::firstOrCreate(['slug' => 'national-political-prisoner-coalition'], ['name' => 'National Political Prisoner Coalition']);

        $slug = 'nppc-launches-online-store';
        $title = 'NPPC Launches Online Store to Fund Political-Prisoner Support';
        $intro = 'The National Political Prisoner Coalition today opened its online store, where supporters can buy shirts, prints, and other merchandise that carry the movement\'s message — and whose proceeds fund letter-writing, commissary drives, legal defense, and the coalition\'s original research.';

        $body = <<<'BODY'
<p><strong>FOR IMMEDIATE RELEASE</strong></p>

<p>The National Political Prisoner Coalition today launched its <a href="/store">online store</a>, offering apparel, prints, and other merchandise in support of political prisoners. Proceeds fund the coalition&rsquo;s core work: prisoner outreach and letter-writing, commissary and defense funds, and the original research behind its database of more than 7,000 political prisoners.</p>

<h2>What&rsquo;s in the store</h2>

<p>The opening collection keeps the coalition&rsquo;s message plain and wearable — including shirts reading &ldquo;Free All Political Prisoners&rdquo; — alongside prints and small goods drawn from the movements the coalition documents. More designs, including pieces built around figures and images from the coalition&rsquo;s archive, will be added over time.</p>

<h2>Where the money goes</h2>

<p>&ldquo;Every prisoner-support movement in this country has run on the same things: stamps, commissary money, bail, and lawyers,&rdquo; a coalition spokesperson said. &ldquo;A shirt is a small thing, but a thousand of them keep the mail moving and the lights on for a family with someone inside.&rdquo;</p>

<p>The coalition is independent, non-partisan, and donor-supported. Store proceeds go directly to its programs — prisoner outreach, mutual aid and defense funds, and the research and archive work that keep these cases from disappearing — rather than to overhead.</p>

<h2>Shop and support</h2>

<p>The store is open now at <a href="/store">the NPPC store</a>. Supporters who would rather give directly can still do so through the coalition&rsquo;s <a href="/donate">donate page</a>, and those looking for other ways to help can <a href="/volunteer">volunteer</a> or write to someone inside through the coalition&rsquo;s prisoner-outreach program.</p>

<p><em>The National Political Prisoner Coalition documents the history and present of political imprisonment in the United States, maintains profiles of more than 7,000 political prisoners across American history, and organizes support for those imprisoned today.</em></p>
BODY;

        $payload = [
            'title'        => $title,
            'intro'        => $intro,
            'body'         => $body,
            'category_id'  => $category->id,
            'author_id'    => $author->id,
            'published_at' => Carbon::parse('2024-11-20 10:00:00'),
        ];

        $existing = Article::where('slug', $slug)->first();
        if ($existing) {
            $existing->update($payload);
            $this->info("Updated: {$title}");
        } else {
            Article::create(['slug' => $slug] + $payload);
            $this->info("Created: {$title}");
        }

        $this->line("Live at /news/{$slug}");

        return self::SUCCESS;
    }
}
