<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Publishes the press release announcing the NPPC Quiz, dated July 20,
 * 2025 per editorial direction. Keyed by slug, idempotent (re-runs
 * update in place).
 */
final class AddNppcQuizPressRelease extends Command {
    protected $signature = 'articles:add-nppc-quiz-press-release';
    protected $description = 'Publish the NPPC Quiz launch press release';

    public function handle(): int {
        // Keyed on the SLUG, not the title — see AddMobileAppPressRelease.
        $category = Category::firstOrCreate(['slug' => 'press-releases'], ['title' => 'Press Releases']);
        $author   = Author::firstOrCreate(['slug' => 'national-political-prisoner-coalition'], ['name' => 'National Political Prisoner Coalition']);

        $slug = 'nppc-launches-the-nppc-quiz';
        $title = 'NPPC Launches the NPPC Quiz, an Interactive Look at Political Repression in America';
        $intro = 'The National Political Prisoner Coalition today launched the NPPC Quiz, a free five-minute interactive quiz that asks where you stand on dissent and state power, measures what you do for the imprisoned, tests your grasp of America’s history of political imprisonment — and challenges you to guess the real numbers behind repression and mass incarceration.';

        $body = <<<'BODY'
<p><strong>FOR IMMEDIATE RELEASE</strong></p>

<p>The National Political Prisoner Coalition today launched the <a href="/nppc-quiz">NPPC Quiz</a>, a free, interactive quiz about political repression and imprisonment in the United States. The quiz takes about five minutes, requires no sign-up, and is anonymous.</p>

<p>The quiz opens onto a wall of faces: Bill Haywood, Voltairine de Cleyre, Joan Little, Ben Fletcher, and other Americans imprisoned across more than a century for their politics, their organizing, or their dissent. What follows asks the taker three questions the coalition believes every American should sit with: Where do you stand? What do you do? And how much of this history do you actually know?</p>

<h2>What the quiz measures</h2>

<p>The NPPC Quiz runs in three parts, with an interlude:</p>

<ul>
<li><strong>Values</strong> — twelve statements about dissent, due process, solidarity with the imprisoned, and the power of the state. There are no right answers; the section maps where the taker stands across four dimensions — the Right to Dissent, Prisoner Solidarity, Due Process, and Resistance — and names the profile that fits: the Dissent Defender, the Prisoner&rsquo;s Ally, the Due Process Guardian, or the Resister.</li>
<li><strong>Engagement</strong> — ten concrete forms of prisoner support, from writing letters and sending commissary to court support, bail funds, and clemency campaigns, scored from Witness to Organizer.</li>
<li><strong>The Numbers</strong> — an interlude that asks takers to guess the scale of repression before showing them the documented figures: the United States holds roughly <strong>one in five of the world&rsquo;s prisoners</strong> with about 4% of its population; about <strong>70% of people in local jails have not been convicted</strong> of any crime; roughly <strong>98% of federal convictions</strong> come from plea deals rather than trials; and of some 780 men held at Guantánamo Bay, only about <strong>2% were ever charged</strong> with anything.</li>
<li><strong>Knowledge</strong> — twelve questions on the history of political imprisonment in America, from Eugene V. Debs and the Espionage Act through the Palmer Raids, the Smith Act, the Hollywood Ten, COINTELPRO, Executive Order 9066, and Attica.</li>
</ul>

<h2>Why a quiz</h2>

<p>Political imprisonment is usually discussed as history or as someone else&rsquo;s emergency. The coalition built the quiz because the numbers say otherwise — and because almost no one guesses them right. Most takers place the United States&rsquo; share of the world&rsquo;s prisoners far below one in five, and assume people in jail have been convicted of something. The gap between what Americans believe about their justice system and what it actually does is, in the coalition&rsquo;s view, exactly where public education has to start.</p>

<p>The quiz ends the way the coalition hopes every encounter with this history ends: with something to do. Takers are pointed to volunteer opportunities, active petitions, and the coalition&rsquo;s prisoner-outreach program, where anyone can write a letter to someone inside.</p>

<h2>Take the quiz</h2>

<p>The NPPC Quiz is available now at <a href="/nppc-quiz">nppc-quiz</a>. It runs in any browser, works on phones, takes about five minutes, and collects no names or emails.</p>

<p><em>The National Political Prisoner Coalition documents the history and present of political imprisonment in the United States, maintains profiles of more than 7,000 political prisoners across American history, and organizes support for those imprisoned today.</em></p>
BODY;

        $payload = [
            'title'        => $title,
            'intro'        => $intro,
            'body'         => $body,
            'category_id'  => $category->id,
            'author_id'    => $author->id,
            'published_at' => Carbon::parse('2025-07-20 10:00:00'),
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
