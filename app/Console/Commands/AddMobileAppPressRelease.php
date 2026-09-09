<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Publishes the press release announcing the forthcoming NPPC mobile app,
 * dated September 15, 2025 per editorial direction. Keyed by slug,
 * idempotent (re-runs update in place).
 */
final class AddMobileAppPressRelease extends Command {
    protected $signature = 'articles:add-mobile-app-press-release';
    protected $description = 'Publish the NPPC mobile app announcement press release';

    public function handle(): int {
        // Keyed on the SLUG, not the title. Keying on the title minted a
        // second "Press Release" category alongside the seeded "Press
        // Releases", and both then showed up as tabs on /news.
        $category = Category::firstOrCreate(['slug' => 'press-releases'], ['title' => 'Press Releases']);
        $author   = Author::firstOrCreate(['slug' => 'national-political-prisoner-coalition'], ['name' => 'National Political Prisoner Coalition']);

        $slug = 'nppc-announces-mobile-app';
        $title = 'NPPC Announces a Mobile App to Put the Political-Prisoner Database in Every Pocket';
        $intro = 'The National Political Prisoner Coalition today announced that it is building a free mobile app for iOS and Android — putting its database of more than 7,000 political prisoners, its letter-writing tools, and a daily "on this day" record of American political repression into the hands of supporters wherever they are.';

        $body = <<<'BODY'
<p><strong>FOR IMMEDIATE RELEASE</strong></p>

<p>The National Political Prisoner Coalition today announced that it is developing a free mobile application for iOS and Android. The app will bring the coalition&rsquo;s core tools — its searchable database of political prisoners, its prisoner-outreach and letter-writing resources, active petitions, and a daily record of political repression in American history — to phones and tablets, with a public release planned for 2026.</p>

<h2>What the app will do</h2>

<p>The coalition&rsquo;s aim is simple: make it as easy to support a political prisoner from a bus seat as from a desk. The first version is being built around four things people already do most on the website:</p>

<ul>
<li><strong>Search the database.</strong> All of the coalition&rsquo;s profiles — more than 7,000 people imprisoned across American history for their politics, organizing, or dissent — searchable by name, era, movement, and place, with sources on every record.</li>
<li><strong>Write a letter.</strong> A guided flow for the coalition&rsquo;s prisoner-outreach program, with current mailing addresses, guidance on what may and may not be sent, and reminders when a birthday or a court date is coming up.</li>
<li><strong>Take action.</strong> Active petitions and clemency campaigns, one tap to sign, and a way to follow a case for updates.</li>
<li><strong>This day in repression.</strong> A daily notification tied to the coalition&rsquo;s calendar — the arrest, the trial, the hunger strike, the release that happened on this date — turning a scroll of history into a habit.</li>
</ul>

<h2>Why an app</h2>

<p>&ldquo;The people we document were criminalized for reaching other people — with a leaflet, a picket line, a printing press,&rdquo; a coalition spokesperson said. &ldquo;The phone is where that reaching happens now. An app that lets someone write to a prisoner during their commute, or learn who was locked up on this day in 1919, is squarely in that tradition.&rdquo;</p>

<p>The app will be free, will carry no advertising, and will not sell or share user data. Reading the database and the daily history will require no account; signing petitions or saving a case to follow will use the same anonymous, minimal-data approach as the website.</p>

<h2>Timeline and how to help</h2>

<p>The coalition is building the app in the open and is looking for testers, mobile developers, and translators to help before launch. Anyone who wants to be notified when the beta opens, or who can lend a hand, can reach the coalition through its <a href="/contact">contact page</a> or <a href="/volunteer">volunteer form</a>. Until the app ships, every one of these tools is already available on the website.</p>

<p><em>The National Political Prisoner Coalition documents the history and present of political imprisonment in the United States, maintains profiles of more than 7,000 political prisoners across American history, and organizes support for those imprisoned today.</em></p>
BODY;

        $payload = [
            'title'        => $title,
            'intro'        => $intro,
            'body'         => $body,
            'category_id'  => $category->id,
            'author_id'    => $author->id,
            'published_at' => Carbon::parse('2025-09-15 10:00:00'),
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
