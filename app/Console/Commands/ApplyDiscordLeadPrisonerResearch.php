<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Institution;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Applies public-source findings recovered while screening the October 2026
 * Jupiter research queue. The Discord posts were discovery leads only; every
 * fact written here is supported by the public sources linked in each profile.
 */
final class ApplyDiscordLeadPrisonerResearch extends Command
{
    protected $signature = 'prisoners:apply-discord-lead-research';

    protected $description = 'Apply public-source prisoner and case findings from the October 2026 research queue';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $this->correctJoshuaWilliams();
            $this->correctJakhiMcCray();
            $this->correctCaseyGoonan();
            $this->enrichLosAngelesSentences();
            $this->mergeDanielSanchezEstradaDuplicate();
            $this->correctPrairielandSentencingDates();
            $this->updateDanDuggan();
            $this->updateYaakubVijandre();
            $this->correctFloridaProChoiceDefendants();
            $this->completePacificBeachDefendants();
            $this->addYehonatanOvadia();
            $this->addTrishaBrownlee();
            $this->addNaqaaHamed();
            $this->addMinnesotaFifteen();
            $this->addHashemiTahmasebiFamily();
            $this->addCatalinaSantiago();
            $this->addNaomiIsaac();
            $this->completeRichmondI95Defendants();
            $this->addMichiganEight();
            $this->addKadeByrand();
            $this->updateCopCityFlyerDefendants();
            $this->addEmilyPhillips();
            $this->addRogelioBolufe();
            $this->addLuisGaleano();
            $this->addJevonMartinez();
            $this->addTarekBazrouk();
            $this->addPaulErvinJohnson();
            $this->addSophieRoske();
            $this->updatePalestineImmigrationAndExtraditionCases();
        });

        Cache::forget(PrisonerApiController::cacheKey());
        $this->info('Applied the public-source prisoner and case research from the Jupiter lead review.');

        return self::SUCCESS;
    }

    private function mergeDanielSanchezEstradaDuplicate(): void
    {
        $canonical = Prisoner::withUnderReview()->where('slug', 'daniel-sanchez-estrada')->first();
        $duplicate = Prisoner::withUnderReview()->where('slug', 'daniel-rolando-sanchez-estrada')->first();

        if (! $duplicate) {
            return;
        }

        if (! $canonical) {
            $duplicate->slug = 'daniel-sanchez-estrada';
            $duplicate->save();

            return;
        }

        $duplicate->cases()->delete();
        $duplicate->delete();
        $this->line('Removed the duplicate Daniel Rolando Sanchez-Estrada profile.');
    }

    private function updateDanDuggan(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'daniel-duggan')->first();
        if (! $prisoner) {
            $this->warn('Daniel Duggan was not found; skipped update.');

            return;
        }

        $prisoner->fill([
            'name' => 'Daniel Edmund Duggan',
            'first_name' => 'Daniel',
            'middle_name' => 'Edmund',
            'last_name' => 'Duggan',
            'aka' => 'Dan Duggan',
            'state' => 'New South Wales',
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => true,
            'description' => 'Daniel “Dan” Edmund Duggan is an Australian citizen and former U.S. Marine Corps pilot who has been held in maximum-security custody in New South Wales since Australian police arrested him on October 21, 2022 at the request of the United States. A U.S. indictment alleges that he conspired to violate arms-export laws, defraud the United States, and launder money in connection with training Chinese military pilots in South Africa. Duggan denies the allegations, says he trained civilian pilots, and argues that the prosecution is political. Australia approved his extradition in December 2024. On April 16, 2026, the Federal Court of Australia dismissed his challenge to that decision; the Australian government said he would remain in extradition custody until surrender to the United States. His custody is therefore Australian detention directly resulting from a U.S. prosecution, rather than an Australian criminal charge.',
            'body' => $this->sources([
                ['Australian Attorney-General’s Department — extradition records', 'https://www.ag.gov.au/rights-and-protections/freedom-information/freedom-information-disclosure-log/foi23065'],
                ['Associated Press — April 16, 2026 appeal ruling and continuing extradition custody', 'https://apnews.com/article/7c90c244a5c7a0c22968ff642f227a22'],
                ['ABC Australia — Federal Court ruling and case history', 'https://www.abc.net.au/news/2026-04-16/former-us-marine-pilot-dan-duggan-to-be-extradited-australia/106570508'],
                ['Signed nationality statement — full name and July 29, 1968 birth date', 'https://michaelwest.com.au/wp-content/uploads/2024/05/6-May-Submission-to-AG-Mark-Dreyfus-March-2024-1-1.pdf'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1968, 7, 29);
        $prisoner->save();

        $case = $prisoner->cases()->first();
        if ($case) {
            $case->charges = 'U.S. indictment alleging conspiracy to defraud the United States, conspiracy to violate the Arms Export Control Act and International Traffic in Arms Regulations, money laundering, and related offenses over alleged training of Chinese military pilots';
            $case->convicted = 'No — detained in Australia pending extradition and trial in the United States';
            $case->sentence = 'Held continuously in maximum-security extradition custody in New South Wales since October 21, 2022; Australia approved extradition in December 2024 and the Federal Court dismissed his challenge on April 16, 2026';
            $case->setPartialDate('arrest_date', 2022, 10, 21);
            $case->setPartialDate('incarceration_date', 2022, 10, 21);
            $case->save();
        }

        $this->line('Updated Daniel Duggan with his full name, exact birth date, and 2026 extradition status.');
    }

    private function updateYaakubVijandre(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'yaakub-ira-vijandre')->first();
        if (! $prisoner) {
            $this->warn('Yaakub Ira Vijandre was not found; skipped update.');

            return;
        }

        $prisoner->fill([
            'name' => 'Yaakub Ira Vijandre',
            'aka' => 'Jacob Ira Azurin Vijandre; Ya’akub Vijandre',
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => true,
            'body' => $this->sources([
                ['Asian Americans Advancing Justice–AAJC — detention, advocacy, and demand for release', 'https://www.advancingjustice-aajc.org/press-release/asian-americans-advancing-justice-aajc-calls-release-yaakub-vijandre-unlawful'],
                ['Muslim Legal Fund of America — amended habeas petition and protected-speech claim', 'https://mlfa.org/for-immediate-release-mr-jacob-yaakub-ira-vijandre-files-an-amended-habeas-petition-challenging-his-unconstitutional-detention-based-on-his-protected-political-speech-and-express/'],
                ['Federal habeas docket — continuing Folkston custody and September 2026 activity', 'https://habeasdockets.org/dockets/docket/3537/'],
                ['Democracy Now — September 2, 2026 habeas ruling and continuing detention', 'https://www.democracynow.org/2026/9/2/headlines/activist_yaakub_vijandre_wins_right_to_challenge_ice_detention_after_nearly_1_year_behind_bars'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1987, null, null, true);
        $prisoner->save();

        $case = $prisoner->cases()->first();
        if ($case) {
            $case->convicted = 'No criminal conviction — held in civil immigration custody while pursuing habeas and removal proceedings';
            $case->setPartialDate('arrest_date', 2025, 10, 7);
            $case->setPartialDate('incarceration_date', 2025, 10, 7);
            $case->save();
        }

        $this->line('Corrected Yaakub Vijandre’s arrest date and added his legal-name variant and current habeas status.');
    }

    private function correctFloridaProChoiceDefendants(): void
    {
        $sources = $this->sources([
            ['U.S. Attorney’s Office — September 12, 2024 sentences and June 14 guilty pleas', 'https://www.justice.gov/usao-mdfl/pr/florida-man-sentenced-civil-rights-conspiracy-targeting-pregnancy-resource-centers'],
            ['U.S. Department of Justice — Gabriella Oropesa’s December 19, 2024 jury conviction', 'https://www.justice.gov/archives/opa/pr/florida-woman-convicted-civil-rights-conspiracy-targeting-pregnancy-resource-centers'],
            ['Eleventh Circuit — 120-day sentence and November 20, 2025 affirmance', 'https://www.govinfo.gov/content/pkg/USCOURTS-ca11-25-10928/pdf/USCOURTS-ca11-25-10928-0.pdf'],
        ]);

        $sentences = [
            'caleb-freestone' => ['One year and one day in federal prison', 366],
            'amber-smith-stewart' => ['Thirty days in prison followed by sixty days of home detention', 30],
            'annarella-rivera' => ['Thirty days in prison followed by sixty days of home detention', 30],
        ];

        foreach ($sentences as $slug => [$sentence, $days]) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                continue;
            }
            $prisoner->body = $sources;
            $prisoner->in_custody = false;
            $prisoner->released = true;
            $prisoner->awaiting_trial = false;
            $prisoner->save();

            $case = $prisoner->cases()->first();
            if ($case) {
                $case->convicted = 'Yes — pleaded guilty June 14, 2024 to conspiracy against rights';
                $case->plead = 'Guilty on June 14, 2024';
                $case->sentence = $sentence;
                $case->documented_imprisoned_for_days = $days;
                $case->setPartialDate('sentenced_date', 2024, 9, 12);
                $case->save();
            }
        }

        $oropesa = Prisoner::withUnderReview()->where('slug', 'gabriella-oropesa')->first();
        if ($oropesa) {
            $oropesa->description = 'Gabriella Victoria Oropesa, also known as Gabs, Gaby, and Gummy, was the only one of four Florida abortion-rights activists in this case to go to trial. A federal jury convicted her on December 19, 2024 of conspiracy against rights for helping plan a series of 2022 nighttime vandalism actions at three crisis-pregnancy centers after the Dobbs decision. The court sentenced her to 120 days in prison followed by three years of supervised release. The Eleventh Circuit affirmed the conviction on November 20, 2025, holding that prosecutors could use 18 U.S.C. § 241 for a conspiracy to violate rights secured by the FACE Act.';
            $oropesa->body = $sources;
            $oropesa->in_custody = false;
            $oropesa->released = true;
            $oropesa->awaiting_trial = false;
            $oropesa->save();

            $oropesa->cases()->delete();
            $case = new PrisonerCase([
                'prisoner_id' => $oropesa->id,
                'charges' => 'Conspiracy against rights under 18 U.S.C. § 241 for planning and participating in vandalism actions targeting Florida crisis-pregnancy centers after Dobbs',
                'convicted' => 'Yes — federal jury verdict December 19, 2024',
                'sentence' => '120 days in federal prison followed by three years of supervised release; conviction affirmed November 20, 2025',
                'documented_imprisoned_for_days' => 120,
            ]);
            $case->setPartialDate('sentenced_date', 2025, 3, 18);
            $case->setPartialDate('release_date', 2025, 8, 24);
            $case->save();
        }

        $this->line('Corrected the Florida abortion-rights defendants’ pleas, sentencing dates, sentence lengths, and duplicate Oropesa case.');
    }

    private function completePacificBeachDefendants(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'San Diego County Jail'],
            ['city' => 'San Diego', 'state' => 'California']
        );
        $sources = $this->sources([
            ['San Diego County District Attorney — all eleven convictions and June 28, 2024 sentencing range', 'https://danewscenter.com/news/eight-antifa-defendants-sentenced-in-pacific-beach-assault-case/'],
            ['ABC 10News — June 28 sentences for Brian Lightfoot and Jeremy White', 'https://www.10news.com/news/local-news/11-sentenced-in-alleged-antifa-counter-protests-in-pacific-beach'],
            ['Times of San Diego — Luis Mora plea and 32-month sentence', 'https://timesofsandiego.com/crime/2024/03/18/antifa-defendant-stuns-sd-court-with-insanity-plea-in-pacific-beach-riot-case/'],
            ['ABC 10News — Nikki Hubbard sentence and charges', 'https://www.10news.com/news/local-news/san-diego-news/one-of-11-indicted-in-pacific-beach-protest-melee-sentenced-to-prison'],
        ]);

        $sentences = [
            'brian-cortez-lightfoot-jr' => ['Two years in state prison, served in county jail', 730, [1997, null, null, true]],
            'jeremy-white' => ['Two years in state prison, served in county jail', 730, [1983, null, null, true]],
            'alexander-akridgejacobs' => ['270 days in county jail as a condition of probation', 270, [1991, null, null, true]],
            'christian-martinez' => ['180 days in county jail followed by probation', 180, [1998, 10, 17, false]],
            'ruchelle-ogden' => ['One year in county jail followed by two years of probation', 365, [1997, 12, 5, false]],
            'bryan-rivera' => ['180 days in county jail followed by two years of probation', 180, [2001, 7, 18, false]],
            'faraz-martin-talab' => ['One year in county jail as a condition of probation', 365, [1994, 8, 12, false]],
            'joseph-austin-gaskins' => ['One year in county jail followed by two years of probation', 365, [2000, 9, 26, false]],
        ];

        foreach ($sentences as $slug => [$sentence, $days, $birth]) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                continue;
            }
            $prisoner->body = $sources;
            $prisoner->setPartialDate('birthdate', $birth[0], $birth[1], $birth[2], $birth[3]);
            $prisoner->save();

            $case = $prisoner->cases()->first();
            if ($case) {
                $case->sentence = $sentence;
                $case->documented_imprisoned_for_days = $days;
                $case->setPartialDate('sentenced_date', 2024, 6, 28);
                $case->setPartialDate('incarceration_date', 2024, 6, 28);
                $case->save();
            }
        }

        $this->upsertEarlierPacificBeachDefendant(
            'jesse-cannon',
            'Jesse Merel Cannon',
            'Jesse',
            'Merel',
            'Cannon',
            1990,
            'Two years for the Pacific Beach case plus a consecutive three years for a separate assault case, for a total five-year state-prison term',
            1826,
            [2024, 2, 13],
            $institution,
            $sources
        );
        $this->upsertEarlierPacificBeachDefendant(
            'erich-louis-yach',
            'Nikki Hubbard',
            'Nikki',
            null,
            'Hubbard',
            1984,
            'Four years for the Pacific Beach case plus eight consecutive months for an unrelated case, for a total term of four years and eight months',
            1704,
            [2022, 11, 10],
            $institution,
            $sources,
            'Erich Louis Yach'
        );
        $this->upsertEarlierPacificBeachDefendant(
            'luis-francisco-mora',
            'Luis Francisco Mora',
            'Luis',
            'Francisco',
            'Mora',
            1992,
            'Two years and eight months in state prison under a plea agreement',
            974,
            [2024, 3, 18],
            $institution,
            $sources
        );

        $this->line('Completed and corrected the eleven Pacific Beach anti-fascist counter-protester records.');
    }

    private function upsertEarlierPacificBeachDefendant(
        string $slug,
        string $name,
        string $firstName,
        ?string $middleName,
        string $lastName,
        int $birthYear,
        string $sentence,
        int $days,
        array $sentencedDate,
        Institution $institution,
        string $sources,
        ?string $aka = null,
    ): void {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $slug]);
        $prisoner->fill([
            'name' => $name,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'aka' => $aka,
            'gender' => $slug === 'erich-louis-yach' ? 'Female' : 'Male',
            'state' => 'California',
            'era' => '2020s',
            'ideologies' => ['Anti-fascist', 'Anti-racist'],
            'affiliation' => ['Pacific Beach anti-fascist counter-protesters'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => $name.' was one of eleven anti-fascist counter-protesters prosecuted after confrontations with supporters of Donald Trump at the January 9, 2021 Patriot March in Pacific Beach, San Diego. Prosecutors used a conspiracy-to-riot theory that defense attorneys and supporters described as an ideologically selective prosecution. '.$name.' pleaded guilty to charges arising from the counter-protest and received '.$sentence.'.',
            'body' => $sources,
        ]);
        $prisoner->setPartialDate('birthdate', $birthYear, null, null, true);
        $prisoner->save();

        $case = $prisoner->cases()->firstOrNew();
        $case->institution_id = $institution->id;
        $case->charges = 'Conspiracy to commit a riot and assault-related charges arising from the January 9, 2021 anti-fascist counter-protest at the Patriot March in Pacific Beach';
        $case->convicted = 'Yes — guilty plea';
        $case->plead = 'Guilty';
        $case->sentence = $sentence;
        $case->documented_imprisoned_for_days = $days;
        $case->setPartialDate('sentenced_date', $sentencedDate[0], $sentencedDate[1], $sentencedDate[2]);
        $case->setPartialDate('incarceration_date', $sentencedDate[0], $sentencedDate[1], $sentencedDate[2]);
        $case->save();
    }

    private function correctJoshuaWilliams(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'joshua-williams')->first();
        if (! $prisoner) {
            $this->warn('Joshua Williams was not found; skipped correction.');

            return;
        }

        $prisoner->fill([
            'name' => 'Joshua Williams',
            'first_name' => 'Joshua',
            'last_name' => 'Williams',
            'aka' => 'Josh Williams',
            'race' => 'Black',
            'gender' => 'Male',
            'state' => 'Missouri',
            'era' => '2010s',
            'ideologies' => ['Black Lives Matter', 'Anti-police'],
            'affiliation' => ['Ferguson uprising'],
            'inmate_number' => '1292002',
            'website' => 'https://www.freejoshwilliams.com/',
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Joshua "Josh" Williams became one of the youngest and most visible participants in the Ferguson uprising after police killed Michael Brown in August 2014. Police arrested him on December 27, 2014 after a protest in Berkeley, Missouri over the police killing of Antonio Martin. Prosecutors accused him of setting a fire inside a QuikTrip convenience store; no one was injured. Williams pleaded guilty to first-degree arson, second-degree burglary, and misdemeanor stealing on November 2, 2015. On December 10, 2015 he received concurrent terms of eight years, five years, and three months. Supporters argued that the unusually severe sentence was intended to make an example of a nineteen-year-old protester. He remained in Missouri custody after a later conviction for possessing a prohibited object in prison prevented the release supporters had expected in 2022. His support network now expects him to be released in early November 2026, but no completed release date has yet been documented.',
            'body' => $this->sources([
                ['Associated Press via CBS News — eight-year sentence, December 10, 2015', 'https://www.cbsnews.com/news/ferguson-st-louis-protester-sentenced-to-8-years-in-quiktrip-arson/'],
                ['The Guardian — December 27, 2014 arrest and charges', 'https://www.theguardian.com/us-news/2014/dec/27/ferguson-protester-joshua-williams-charged-arson'],
                ['Free Josh Williams — support and Missouri DOC number', 'https://www.freejoshwilliams.com/contact'],
                ['Black Ink — expected early-November 2026 release', 'https://black-ink.info/2026/09/24/help-him-get-home/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1995, 11, 25);
        $prisoner->save();

        // The old row combined this Missouri case with a Norfolk County,
        // Massachusetts jail record belonging to another person.
        $prisoner->cases()->delete();
        $institution = Institution::firstOrCreate(
            ['name' => 'Missouri Department of Corrections'],
            ['state' => 'Missouri', 'mailing_address' => 'C/O Digital Mail Center-Missouri DOC, PO Box 25678, Tampa, FL 33622-5678']
        );
        $case = new PrisonerCase([
            'prisoner_id' => $prisoner->id,
            'institution_id' => $institution->id,
            'charges' => 'First-degree arson; second-degree burglary; misdemeanor stealing — fire inside a QuikTrip during the December 24, 2014 Berkeley, Missouri protest after the police killing of Antonio Martin',
            'plead' => 'Guilty on November 2, 2015',
            'convicted' => 'Yes',
            'sentence' => 'Eight years for arson, five years for burglary, and three months for stealing, concurrent; a later 2021 prison-contraband conviction extended custody. Supporters report an expected release in early November 2026.',
        ]);
        $case->setPartialDate('arrest_date', 2014, 12, 27);
        $case->setPartialDate('incarceration_date', 2015, 9, 22);
        $case->setPartialDate('sentenced_date', 2015, 12, 10);
        $case->save();

        $this->line('Corrected Joshua Williams and removed the unrelated Massachusetts case data.');
    }

    private function correctJakhiMcCray(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'jakhi-mccray')->first();
        if (! $prisoner) {
            return;
        }

        $prisoner->aka = 'Jakhi Isaiah; Jakhi Lodgson-McCray';
        $prisoner->in_custody = false;
        $prisoner->released = true;
        $prisoner->awaiting_trial = true;
        $prisoner->body = $this->sources([
            ['U.S. Attorney, Eastern District of New York — guilty plea', 'https://www.justice.gov/usao-edny/pr/brooklyn-man-pleads-guilty-setting-nypd-vehicles-ablaze'],
            ['Jakhi solidarity campaign', 'https://jakhisolidarity.noblogs.org/'],
        ]);
        $prisoner->save();

        $case = $prisoner->cases()->first();
        if ($case) {
            $case->plead = 'Guilty on April 8, 2026';
            $case->convicted = 'Yes — guilty plea';
            $case->sentence = 'Awaiting sentencing on October 30, 2026; statutory range of 5 to 20 years.';
            $case->save();
        }

        $this->line('Added Jakhi McCray naming variants and current sentencing status.');
    }

    private function correctCaseyGoonan(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'casey-goonan')->first();
        if (! $prisoner) {
            return;
        }

        $prisoner->fill([
            'first_name' => 'Casey',
            'middle_name' => 'Robert',
            'last_name' => 'Goonan',
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => false,
            'description' => 'Casey Robert Goonan is a Bay Area Palestine-solidarity activist convicted in a federal prosecution arising from four June 2024 fire attacks at the University of California, Berkeley and an attempted firebombing at the Ronald V. Dellums Federal Building in Oakland. Federal agents arrested Goonan on June 17, 2024. Goonan pleaded guilty in April 2025 to maliciously damaging federal property by fire. On September 23, 2025, U.S. District Judge Jeffrey S. White sentenced Goonan to 235 months in federal prison, followed by 15 years of supervised release, and ordered $94,267.51 in restitution.',
            'body' => $this->sources([
                ['U.S. Attorney, Northern District of California — September 23, 2025 sentencing', 'https://www.justice.gov/usao-ndca/pr/domestic-terrorist-sentenced-more-19-years-prison-firebombing-university-police-car'],
                ['Free Casey Goonan support campaign', 'https://freecaseygoonan.noblogs.org/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1991, 4, 25);
        $prisoner->save();

        $case = $prisoner->cases()->first();
        if ($case) {
            $case->charges = 'Malicious damage to property owned or possessed by an institution receiving federal financial assistance by means of fire or explosive (18 U.S.C. § 844(f)(1))';
            $case->plead = 'Guilty in April 2025';
            $case->convicted = 'Yes — guilty plea';
            $case->judge = 'Jeffrey S. White';
            $case->sentence = '235 months in federal prison, 15 years of supervised release, and $94,267.51 restitution';
            $case->setPartialDate('arrest_date', 2024, 6, 17);
            $case->setPartialDate('incarceration_date', 2024, 6, 17);
            $case->setPartialDate('sentenced_date', 2025, 9, 23);
            $case->save();
        }

        $this->line('Corrected Casey Goonan arrest, sentence date, sentence, judge, and biography.');
    }

    private function enrichLosAngelesSentences(): void
    {
        $updates = [
            'emiliano-garduno-galvez' => [
                'birth' => 2002,
                'judge' => 'André Birotte Jr.',
                'plead' => 'Guilty in October 2025',
                'body' => [['U.S. Attorney, Central District of California — four-year sentence', 'https://www.justice.gov/usao-cdca/pr/illegal-immigrant-mexico-sentenced-4-years-federal-prison-throwing-molotov-cocktail']],
            ],
            'jacob-daniel-terrazas' => [
                'birth' => 1995,
                'judge' => 'Percy Anderson',
                'plead' => 'Guilty on January 20, 2026',
                'body' => [['U.S. Attorney, Central District of California — ten-month sentence', 'https://www.justice.gov/usao-cdca/pr/paramount-man-sentenced-federal-prison-throwing-cinderblock-border-patrol-agent-during']],
            ],
            'ismael-vega' => [
                'birth' => 1984,
                'judge' => 'John F. Walter',
                'plead' => 'Guilty on April 29, 2026',
                'sentence' => 'Thirty-seven months in federal prison and $253,415 in restitution.',
                'body' => [['U.S. Attorney, Central District of California — 37-month sentence', 'https://www.justice.gov/usao-cdca/pr/westlake-man-sentenced-more-3-years-prison-throwing-rocks-and-lighted-debris-chp']],
            ],
            'yachua-mauricio-flores' => [
                'birth' => 2003,
                'judge' => null,
                'plead' => 'Guilty on April 27, 2026',
                'inmate_number' => '20795-512',
                'body' => [
                    ['Los Angeles Times — 37-month sentence', 'https://www.latimes.com/california/story/2026-08-03/anti-ice-protester-accused-of-pouring-lighter-fluid-on-chp-car-is-sentenced-to-prison'],
                    ['U.S. District Court, Central District of California — August 3, 2026 calendar', 'https://apps.cacd.uscourts.gov/JpsApi/file/c58e6693-625f-4440-6a8e-08deef0caed9'],
                ],
            ],
        ];

        foreach ($updates as $slug => $values) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                continue;
            }

            $prisoner->in_custody = true;
            $prisoner->released = false;
            $prisoner->awaiting_trial = false;
            $prisoner->body = $this->sources($values['body']);
            if (! empty($values['inmate_number'])) {
                $prisoner->inmate_number = $values['inmate_number'];
            }
            $prisoner->setPartialDate('birthdate', $values['birth'], null, null, true);
            $prisoner->save();

            $case = $prisoner->cases()->first();
            if ($case) {
                $case->judge = $values['judge'];
                $case->plead = $values['plead'];
                if (! empty($values['sentence'])) {
                    $case->sentence = $values['sentence'];
                }
                $case->save();
            }
        }

        $this->line('Added age-derived birth years and missing plea, judge, inmate, and restitution details to four Los Angeles protest cases.');
    }

    private function correctPrairielandSentencingDates(): void
    {
        $updates = [
            'benjamin-song' => ['2026-06-23', 'Mark T. Pittman', 'Hanil', 'Champagne'],
            'maricela-rueda' => ['2026-06-23', 'Mark T. Pittman', null, null],
            'elizabeth-soto' => ['2026-06-23', 'Mark T. Pittman', null, null],
            'meagan-morris' => ['2026-06-23', 'Mark T. Pittman', null, 'Bradford Morris'],
            'bradford-morris' => ['2026-06-23', 'Mark T. Pittman', null, 'Meagan Morris'],
            'cameron-arnold' => ['2026-06-23', 'Reed O’Connor', null, 'Autumn Hill'],
            'zachary-evetts' => ['2026-06-23', 'Reed O’Connor', null, null],
            'savanna-batten' => ['2026-06-23', 'Reed O’Connor', null, null],
            'daniel-sanchez-estrada' => ['2026-06-23', 'Reed O’Connor', 'Rolando', 'Des'],
            'ines-soto' => ['2026-07-01', 'Reed O’Connor', null, null],
            'joy-gibson' => ['2026-07-01', 'Reed O’Connor', null, 'Rowan'],
            'rebecca-morgan' => ['2026-07-01', 'Reed O’Connor', null, null],
        ];

        $sources = $this->sources([
            ['Prairieland defendants — June 23 sentencing court notes', 'https://prairielanddefendants.com/court-notes/federal-sentencing-eight-prairieland-defendants-sentenced-30-to-100-years/'],
            ['Prairieland defendants — July 1 sentencing court notes', 'https://prairielanddefendants.com/court-notes/federal-sentencing-day-2-seven-more-defendants-sentenced-22-months-to-50-years/'],
            ['Associated Press — June 23 sentences', 'https://apnews.com/article/1eb7a8ac32dbb637e027709ae010f374'],
            ['Associated Press — July 1 sentences', 'https://apnews.com/article/bbf982ce477d231d44aaba49ac20f70e'],
        ]);

        foreach ($updates as $slug => [$date, $judge, $middleName, $aka]) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                continue;
            }

            if ($middleName) {
                $prisoner->middle_name = $middleName;
            }
            if ($aka) {
                $prisoner->aka = $aka;
            }
            $prisoner->body = $sources;
            $prisoner->save();

            $case = $prisoner->cases()->whereNotNull('sentence')->orderByDesc('sentenced_date')->first()
                ?? $prisoner->cases()->first();
            if (! $case) {
                continue;
            }

            [$year, $month, $day] = array_map('intval', explode('-', $date));
            $case->setPartialDate('sentenced_date', $year, $month, $day);
            $case->judge = $judge;
            $case->save();
        }

        $this->line('Replaced month-only Prairieland sentence dates with the exact June 23 and July 1, 2026 dates and added the sentencing judges.');
    }

    private function addYehonatanOvadia(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'yehonatan-ovadia']);
        $prisoner->fill([
            'name' => 'Yehonatan Ovadia',
            'first_name' => 'Yehonatan',
            'last_name' => 'Ovadia',
            'gender' => 'Male',
            'state' => 'New York',
            'era' => '2020s',
            'ideologies' => ['Anti-Zionism', 'Pro-Palestine', 'Religious freedom'],
            'affiliation' => ['Satmar Hasidic community'],
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'Yehonatan Ovadia is an Israeli-born Satmar Hasidic religious teacher and father of five who moved to the United States on a religious-worker visa in 2022. He participated in anti-Zionist and pro-Palestinian demonstrations in New York and New Jersey. After a November 2023 protest at Ramapo Town Hall, he was charged with hate-crime counts tied to the removal of an Israeli flag; those counts were dropped, and in February 2024 he pleaded guilty to disorderly conduct and paid a fine. U.S. immigration authorities later cited that protest when they denied an extension of his status. ICE arrested Ovadia after an immigration-court appearance in Manhattan on August 28, 2026. He remains detained at the Metropolitan Detention Center in Brooklyn while facing removal to Israel. His habeas lawyers argue that the government retaliated against protected political and religious speech.',
            'body' => $this->sources([
                ['New York Jewish Week — immigration detention and protest history', 'https://www.jta.org/2026/09/30/ny/ice-targets-an-anti-israel-protester-this-time-a-hasidic-jew'],
                ['Ovadia v. Francis habeas docket — August 28, 2026 detention chronology', 'https://habeasdockets.org/dockets/docket/81030/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1991, null, null, true);
        $prisoner->save();

        $institution = Institution::firstOrCreate(
            ['name' => 'Metropolitan Detention Center, Brooklyn'],
            ['city' => 'Brooklyn', 'state' => 'New York']
        );
        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'ICE detention and removal proceedings after denial of an extension of religious-worker status',
        ]);
        $case->institution_id = $institution->id;
        $case->setPartialDate('arrest_date', 2026, 8, 28);
        $case->setPartialDate('incarceration_date', 2026, 8, 28);
        $case->save();

        $this->line('Added Yehonatan Ovadia.');
    }

    private function addRiverAkemann(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'river-akemann']);
        $prisoner->fill([
            'name' => 'River Akemann',
            'first_name' => 'River',
            'last_name' => 'Akemann',
            'state' => 'Wisconsin',
            'era' => '2020s',
            'ideologies' => ['Environmental Justice', 'Indigenous sovereignty', 'Press freedom'],
            'affiliation' => ['Unicorn Riot'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'River Akemann is a video journalist and Unicorn Riot contributor arrested in Ashland County, Wisconsin on August 24, 2026 while documenting Enbridge Line 5 construction near the Bad River Reservation. Akemann was monitoring a creek where opponents said Enbridge lacked authorization to install the pipeline. Police charged Akemann with four misdemeanors—disorderly conduct, trespassing, unlawful assembly, and resisting arrest—and released Akemann later that day. The arrest occurred amid litigation by the Bad River Band and environmental organizations challenging the Line 5 reroute and its water-crossing permits.',
            'body' => $this->sources([
                ['Unicorn Riot — arrest, charges, and same-day release', 'https://unicornriot.ninja/2026/journalist-arrested-while-documenting-legally-contested-enbridge-line-5-construction/'],
            ]),
        ]);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Disorderly conduct; trespassing; unlawful assembly; resisting arrest while documenting Enbridge Line 5 construction',
        ]);
        $case->setPartialDate('arrest_date', 2026, 8, 24);
        $case->setPartialDate('release_date', 2026, 8, 24);
        $case->save();

        $this->line('Added River Akemann.');
    }

    private function addTrishaBrownlee(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'trisha-brownlee']);
        $prisoner->fill([
            'name' => 'Trisha Brownlee',
            'first_name' => 'Trisha',
            'last_name' => 'Brownlee',
            'gender' => 'Female',
            'state' => 'New Jersey',
            'era' => '2020s',
            'ideologies' => ['Press freedom', 'Immigrant rights'],
            'affiliation' => ['Independent press'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'Trisha Brownlee is an independent photographer and disabled military veteran arrested by New Jersey State Police on June 11, 2026 while reporting on two community members who were observing ICE operations in Hardwick Township. Brownlee said she was interviewing the men from their vehicle and did not know that the vehicle contained guns or loose ammunition. Police charged her with possessing prohibited armor-piercing ammunition. She spent 47 days in the Warren County Correctional Center, where her family said she went without medication for Crohn’s disease, before a judge released her on July 27, 2026 under pretrial supervision. The charge remains pending.',
            'body' => $this->sources([
                ['U.S. Press Freedom Tracker — arrest, charge, 47-day detention, and release', 'https://pressfreedomtracker.us/all-incidents/photojournalist-arrested-mid-interview-incarcerated-since-june/'],
            ]),
        ]);
        $prisoner->save();

        $institution = Institution::firstOrCreate(
            ['name' => 'Warren County Correctional Center'],
            ['city' => 'Belvidere', 'state' => 'New Jersey']
        );
        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Prohibited weapons: possession of armor-piercing ammunition',
        ]);
        $case->institution_id = $institution->id;
        $case->documented_imprisoned_for_days = 47;
        $case->setPartialDate('arrest_date', 2026, 6, 11);
        $case->setPartialDate('incarceration_date', 2026, 6, 11);
        $case->setPartialDate('release_date', 2026, 7, 27);
        $case->save();

        $this->line('Added Trisha Brownlee with her 47-day jail period.');
    }

    private function addNaqaaHamed(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'naqaa-hamed']);
        $prisoner->fill([
            'name' => 'Naqaa Hamed',
            'first_name' => 'Naqaa',
            'last_name' => 'Hamed',
            'gender' => 'Female',
            'state' => 'Palestine',
            'era' => '2020s',
            'ideologies' => ['Press freedom'],
            'affiliation' => ['4D Media', 'Press TV'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Naqaa Hamed is a Palestinian-American freelance journalist and Press TV correspondent. Israeli forces arrested her at a temporary military checkpoint near Tuqu’, outside Bethlehem, on September 15, 2026 as she returned from a reporting assignment in Masafer Yatta for a TRT documentary. Authorities questioned her about her journalism and investigated allegations of trading with an enemy, incitement, and support for Iran. A military court repeatedly extended her detention. She was released on bail on September 28 after nearly two weeks in custody, on payment of 8,000 shekels.',
            'body' => $this->sources([
                ['Committee to Protect Journalists — arrest and investigation', 'https://cpj.org/data/people/naqaa-hamed/'],
                ['Palestinian Prisoner’s Society — September 28, 2026 release on bail', 'https://www.ppsmo.ps/home/news/18505?culture=ar-SA'],
            ]),
        ]);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Investigated on suspicion of trading with an enemy, incitement, and support for Iran in connection with journalistic work',
        ]);
        $case->setPartialDate('arrest_date', 2026, 9, 15);
        $case->setPartialDate('incarceration_date', 2026, 9, 15);
        $case->setPartialDate('release_date', 2026, 9, 28);
        $case->save();

        $this->line('Added Palestinian-American journalist Naqaa Hamed and her September 2026 detention.');
    }

    private function addMinnesotaFifteen(): void
    {
        $people = [
            ['Isaac Auman Sant', 'Isaac', 'Auman', 'Sant', 'Isaac Dalto; Ike', 'Conspiracy to impede or injure a federal officer; interstate stalking'],
            ['Emmett James Doyle', 'Emmett', 'James', 'Doyle', 'Plotnikov', 'Conspiracy to impede or injure a federal officer'],
            ['Cameron Kennedy', 'Cameron', null, 'Kennedy', 'Cam; Olive Knite; Knite', 'Conspiracy to impede or injure a federal officer'],
            ['Callum Robinet', 'Callum', null, 'Robinet', 'Juliet K; Juliet; Cal', 'Conspiracy to impede or injure a federal officer'],
            ['Erik Davis', 'Erik', null, 'Davis', 'Errico', 'Conspiracy to impede or injure a federal officer'],
            ['Brian Stillwell Apland', 'Brian', 'Stillwell', 'Apland', 'Tiny', 'Conspiracy to impede or injure a federal officer'],
            ['Hannah Margaret Van De Water Davis', 'Hannah', 'Margaret', 'Van De Water Davis', 'Gabriel Van De Water; Nube', 'Conspiracy to impede or injure a federal officer'],
            ['Treasure Cay Thoreson', 'Treasure', 'Cay', 'Thoreson', 'Schatzi', 'Conspiracy to impede or injure a federal officer'],
            ['Nathan Junho Kim', 'Nathan', 'Junho', 'Kim', 'Moon Bear', 'Conspiracy to impede or injure a federal officer'],
            ['Alec Stewart', 'Alec', null, 'Stewart', 'Mac', 'Conspiracy to impede or injure a federal officer'],
            ['Douglas Misterek', 'Douglas', null, 'Misterek', 'Doug; D Munny Big Dog Orf Orf', 'Conspiracy to impede or injure a federal officer'],
            ['Dustin Scott Beisell', 'Dustin', 'Scott', 'Beisell', 'Sparky', 'Conspiracy to impede or injure a federal officer'],
            ['William Morgan', 'William', null, 'Morgan', 'Willow; Willow Tree', 'Conspiracy to impede or injure a federal officer; interstate stalking; assault on a federal officer; destruction of government property'],
            ['Natasha Rakotz', 'Natasha', null, 'Rakotz', 'Anuran', 'Conspiracy to impede or injure a federal officer; assault on a federal officer'],
        ];

        foreach ($people as [$name, $first, $middle, $last, $aka, $charges]) {
            $slug = str($name)->slug()->toString();
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $slug]);
            $prisoner->fill([
                'name' => $name,
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'aka' => $aka,
                'state' => 'Minnesota',
                'era' => '2020s',
                'ideologies' => ['Anti-fascism', 'Immigrant rights', 'Anti-ICE'],
                'affiliation' => ['Minnesota 15', 'Direct Action Minnesota'],
                'in_custody' => false,
                'released' => true,
                'awaiting_trial' => true,
                'under_review' => false,
                'description' => $name.' is one of the Minnesota 15, a group federally indicted in June 2026 over organizing and demonstrations opposing Operation Metro Surge and immigration enforcement in the Twin Cities. The indictment treats a series of meetings, rapid-response monitoring, blockades, and protests as a conspiracy to impede federal officers. Prosecutors associated the defendants with Direct Action Minnesota and antifascist organizing; defense lawyers and the Minnesota 15 support campaign describe the case as an effort to criminalize protected organizing. The charges remain allegations, and the case is pending.',
                'body' => $this->sources([
                    ['U.S. Department of Justice — indictment announcement and defendant-specific charges', 'https://www.justice.gov/opa/pr/15-members-direct-action-minnesota-minneapolis-based-direct-action-group-antifa-ties'],
                    ['Minnesota 15 defense campaign — case overview', 'https://minnesota15.org/charges'],
                    ['Associated Press — charging and defense context', 'https://apnews.com/article/98e30301d67d3a368efbd8fafa72bf17'],
                ]),
            ]);
            $prisoner->save();

            $case = PrisonerCase::firstOrNew([
                'prisoner_id' => $prisoner->id,
                'charges' => $charges,
            ]);
            $case->indicted = 'Indicted in United States v. Sant et al., No. 0:26-cr-00115 (D. Minn.), unsealed June 16, 2026';
            $case->prosecutor = 'U.S. Attorney Daniel N. Rosen, District of Minnesota';
            $case->save();
        }

        $this->line('Added the fourteen Minnesota 15 defendants missing from the live database; retained the existing Kyle Wagner profile.');
    }

    private function addHashemiTahmasebiFamily(): void
    {
        $sources = $this->sources([
            ['Associated Press — detention, stated basis, and absence of individual allegations', 'https://apnews.com/article/iran-deportation-hostage-crisis-green-cards-rubio-eb22040e26873f6ff0c8b22b3fe76457'],
            ['Tahmasebi v. Mullin habeas docket — continuing federal challenge', 'https://habeasdockets.org/dockets/docket/46942/'],
            ['Hashemi v. Mullin habeas docket — consolidated federal challenge', 'https://habeasdockets.org/dockets/docket/47459/'],
        ]);

        $people = [
            [
                'slug' => 'maryam-tahmasebi',
                'name' => 'Maryam Tahmasebi',
                'first' => 'Maryam',
                'last' => 'Tahmasebi',
                'gender' => 'Female',
                'institution' => ['South Texas Family Residential Center', 'Dilley'],
                'description' => 'Maryam Tahmasebi is an Iranian-born psychology and statistics professor who had lived in the United States for more than a decade as a lawful permanent resident. Federal agents detained her and her teenage son in Los Angeles in April 2026 after the State Department revoked the family’s green cards. The government tied the action to the political history of her mother-in-law, former Iranian vice president Masoumeh Ebtekar, who served as a spokesperson during the 1979 U.S. Embassy hostage crisis. The family’s lawyer told the Associated Press that officials had made no specific allegation against Tahmasebi, her husband, or their son beyond the family relationship. Tahmasebi has been held at the South Texas Family Residential Center in Dilley while challenging her detention and proposed removal through a federal habeas case. The public docket remained active in September 2026 and did not record a release.',
            ],
            [
                'slug' => 'seyed-eissa-hashemi',
                'name' => 'Seyed Eissa Hashemi',
                'first' => 'Seyed Eissa',
                'last' => 'Hashemi',
                'gender' => 'Male',
                'institution' => ['South Texas ICE Processing Center', 'Pearsall'],
                'description' => 'Seyed Eissa Hashemi is an Iranian-born psychology and business professor who had lived in the United States for more than a decade as a lawful permanent resident. Federal agents detained him in Los Angeles in April 2026 after the State Department revoked the green cards of Hashemi, his wife Maryam Tahmasebi, and their teenage son. Officials based the action on Hashemi’s relationship to his mother, former Iranian vice president Masoumeh Ebtekar, who served as a spokesperson during the 1979 U.S. Embassy hostage crisis. The family’s lawyer told the Associated Press that officials had made no specific allegation against any of the three beyond that relationship. Hashemi has been held separately from his wife and son at the South Texas ICE Processing Center in Pearsall while challenging the detention and proposed removal in federal court. His habeas case was consolidated with the family’s lead case, whose public docket remained active in September 2026 without recording a release.',
            ],
        ];

        foreach ($people as $values) {
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $values['slug']]);
            $prisoner->fill([
                'name' => $values['name'],
                'first_name' => $values['first'],
                'last_name' => $values['last'],
                'gender' => $values['gender'],
                'state' => 'California',
                'era' => '2020s',
                'ideologies' => ['Immigrant rights', 'Due process'],
                'affiliation' => ['Hashemi–Tahmasebi family'],
                'in_custody' => true,
                'released' => false,
                'awaiting_trial' => true,
                'under_review' => false,
                'description' => $values['description'],
                'body' => $sources,
            ]);
            $prisoner->save();

            $institution = Institution::firstOrCreate(
                ['name' => $values['institution'][0]],
                ['city' => $values['institution'][1], 'state' => 'Texas']
            );
            $case = PrisonerCase::firstOrNew([
                'prisoner_id' => $prisoner->id,
                'charges' => 'Civil immigration detention and removal proceedings after the State Department terminated lawful permanent resident status on foreign-policy grounds tied to a relative’s political history',
            ]);
            $case->institution_id = $institution->id;
            $case->convicted = 'No — civil immigration detention; no individual criminal allegation reported';
            $case->setPartialDate('arrest_date', 2026, 4);
            $case->setPartialDate('incarceration_date', 2026, 4);
            $case->save();
        }

        $this->line('Added Maryam Tahmasebi and Seyed Eissa Hashemi with their continuing April 2026 immigration detention cases.');
    }

    private function addCatalinaSantiago(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'catalina-xochitl-santiago-santiago']);
        $prisoner->fill([
            'name' => 'Catalina “Xóchitl” Santiago Santiago',
            'first_name' => 'Catalina',
            'middle_name' => 'Xóchitl',
            'last_name' => 'Santiago Santiago',
            'aka' => 'Xóchitl Santiago',
            'gender' => 'Female',
            'race' => 'Indigenous Zapotec',
            'state' => 'Texas',
            'era' => '2020s',
            'ideologies' => ['Immigrant rights', 'Indigenous rights'],
            'affiliation' => ['La Mujer Obrera'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Catalina “Xóchitl” Santiago Santiago is a Zapotec immigrant-rights organizer and DACA recipient who has lived in the United States since childhood. Border Patrol agents arrested her at El Paso International Airport on August 3, 2025 while she was traveling to a work conference for La Mujer Obrera, even though her DACA work authorization remained valid. She was held at the El Paso Processing Center while the government initiated removal proceedings. On October 1, 2025, U.S. District Judge Kathleen Cardone ruled that her arrest and continued detention violated procedural due process and ordered her immediate release. ICE released Santiago that evening after 59 days in custody.',
            'body' => $this->sources([
                ['Santiago Santiago v. Noem habeas docket — arrest and release order', 'https://habeasdockets.org/dockets/docket/1658/'],
                ['Texas Tribune — October 1, 2025 release', 'https://www.texastribune.org/2025/10/01/texas-daca-deportation-el-paso-catalina-xochitl-santiago-court-ruling/'],
                ['The Guardian — organizing background and detention', 'https://www.theguardian.com/us-news/2025/aug/27/daca-recipient-detention-immigration'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1997, null, null, true);
        $prisoner->save();

        $institution = Institution::firstOrCreate(
            ['name' => 'El Paso Processing Center'],
            ['city' => 'El Paso', 'state' => 'Texas']
        );
        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Civil immigration detention and removal proceedings despite active Deferred Action for Childhood Arrivals protection',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — federal court ordered release on procedural due-process grounds';
        $case->documented_imprisoned_for_days = 59;
        $case->setPartialDate('arrest_date', 2025, 8, 3);
        $case->setPartialDate('incarceration_date', 2025, 8, 3);
        $case->setPartialDate('release_date', 2025, 10, 1);
        $case->save();

        $this->line('Added Catalina “Xóchitl” Santiago Santiago and her 59-day ICE detention.');
    }

    private function addNaomiIsaac(): void
    {
        $prisoner = Prisoner::withUnderReview()
            ->whereIn('slug', ['naomi-destiny-isaac', 'naomi-nomi-isaac'])
            ->first() ?? new Prisoner(['slug' => 'naomi-destiny-isaac']);
        $prisoner->fill([
            'name' => 'Naomi “Nomi” Isaac',
            'first_name' => 'Naomi',
            'last_name' => 'Isaac',
            'aka' => 'Nomi Isaac',
            'gender' => 'Nonbinary',
            'state' => 'Virginia',
            'era' => '2020s',
            'ideologies' => ['Anti-War', 'Pro-Palestine', 'Black liberation'],
            'affiliation' => ['Virginia Student Power Network', 'Black Alliance for Peace'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Naomi “Nomi” Isaac is a Richmond organizer with the Virginia Student Power Network and Black Alliance for Peace. On March 11, 2024, Isaac joined a Palestine-solidarity action that blocked Interstate 95 and Interstate 64 near Richmond. Nine demonstrators locked themselves together with ladders and “sleeping dragon” devices to demand an end to U.S. support for Israel’s war in Gaza. Virginia State Police arrested Isaac and the other participants. On June 24, 2024, Richmond General District Court Judge Mansi Shah convicted eight participants, including Isaac, of stopping traffic and imposed five days in jail and a $300 fine. Isaac told the court that the action was intended to oppose war and connect Palestinian liberation with Black and Indigenous struggles.',
            'body' => $this->sources([
                ['VPM — conviction, five-day jail sentence, and Isaac’s organizing background', 'https://www.vpm.org/news/2024-06-24/gaza-protest-interstate-95-sentence-jail/'],
                ['VPM — March 11, 2024 action and arrests', 'https://www.vpm.org/news/2024-03-11/richmond-virginia-interstate-protest-sleeping-dragon-israel-palestine/'],
            ]),
        ]);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Stopping traffic during a Palestine-solidarity blockade of Interstate 95 and Interstate 64',
        ]);
        $case->convicted = 'Yes — convicted of stopping traffic; other misdemeanor counts were dismissed';
        $case->judge = 'Mansi Shah';
        $case->sentence = 'Five days in jail and a $300 fine';
        $case->documented_imprisoned_for_days = 5;
        $case->setPartialDate('arrest_date', 2024, 3, 11);
        $case->setPartialDate('sentenced_date', 2024, 6, 24);
        $case->save();

        $this->line('Added Naomi “Nomi” Isaac and the Richmond I-95 Palestine-solidarity case.');
    }

    private function completeRichmondI95Defendants(): void
    {
        $sources = $this->sources([
            ['12 On Your Side — all nine defendants and their jail sentences', 'https://www.12onyourside.com/2024/06/25/9-sentenced-jail-after-protesting-i-95/'],
            ['WTVR CBS 6 — defendants and overnight pretrial detention', 'https://www.wtvr.com/news/local-news/free-palestine-protesters-released-from-richmond-jail-march-12-2024'],
            ['VPM — trial, protest context, and sentencing', 'https://www.vpm.org/news/2024-06-24/gaza-protest-interstate-95-sentence-jail/'],
        ]);
        $people = [
            ['zayneabideen-rasul-al-murshidi', 'Zayneabideen Rasul Al-Murshidi', 'Zayneabideen', null, 'Rasul Al-Murshidi', 2000, 5],
            ['max-hudson-gray-holland', 'Max Hudson Gray Holland', 'Max', 'Hudson Gray', 'Holland', 1999, 5],
            ['charles-d-caines', 'Charles D. Caines', 'Charles', 'D.', 'Caines', 2000, 5],
            ['kemp-walker-barber', 'Kemp Walker Barber', 'Kemp', 'Walker', 'Barber', 1998, 1],
            ['connor-joris-mccarty', 'Connor Joris McCarty', 'Connor', 'Joris', 'McCarty', 2002, 5],
            ['naomi-destiny-isaac', 'Naomi Destiny Isaac', 'Naomi', 'Destiny', 'Isaac', 1998, 5],
            ['sarah-elizabeth-milkowski-dahlgren', 'Sarah Elizabeth Milkowski Dahlgren', 'Sarah', 'Elizabeth', 'Milkowski Dahlgren', null, 5],
            ['kenrick-keith-cameron-jr', 'Kenrick Keith Cameron Jr.', 'Kenrick', 'Keith', 'Cameron Jr.', 1998, 5],
            ['jasmine-juliet-cuellar', 'Jasmine Juliet Cuellar', 'Jasmine', 'Juliet', 'Cuellar', 1993, 5],
        ];

        foreach ($people as [$slug, $name, $first, $middle, $last, $birthYear, $days]) {
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $slug]);
            $prisoner->fill([
                'name' => $name,
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'state' => 'Virginia',
                'era' => '2020s',
                'ideologies' => ['Anti-War', 'Pro-Palestine'],
                'in_custody' => false,
                'released' => true,
                'awaiting_trial' => false,
                'under_review' => false,
                'description' => $name.' joined a March 11, 2024 Palestine-solidarity action that blocked Interstate 95 near Richmond. Nine demonstrators linked themselves with ladders, chains, and “sleeping dragon” devices to demand an end to United States support for Israel’s war in Gaza. Virginia State Police arrested the group. On June 24, Richmond General District Court Judge Mansi Shah imposed a jail sentence for stopping traffic; '.$name.' received '.($days === 1 ? 'one day' : 'five days').' in jail.',
                'body' => $sources,
            ]);
            if ($slug === 'naomi-destiny-isaac') {
                $prisoner->aka = 'Nomi Isaac';
                $prisoner->gender = 'Nonbinary';
                $prisoner->affiliation = ['Virginia Student Power Network', 'Black Alliance for Peace'];
            }
            if ($birthYear) {
                $prisoner->setPartialDate('birthdate', $birthYear, null, null, true);
            }
            $prisoner->save();

            $case = $prisoner->cases()->first() ?? new PrisonerCase(['prisoner_id' => $prisoner->id]);
            $case->charges = 'Stopping another vehicle to impede its progress during a Palestine-solidarity blockade of Interstate 95; other initial misdemeanor counts were dismissed or dropped';
            $case->convicted = 'Yes — convicted of stopping traffic';
            $case->judge = 'Mansi Shah';
            $case->sentence = ($days === 1 ? 'One day' : 'Five days').' in jail'.($days === 5 ? ' and a $300 fine' : '');
            $case->documented_imprisoned_for_days = $days;
            $case->setPartialDate('arrest_date', 2024, 3, 11);
            $case->setPartialDate('sentenced_date', 2024, 6, 24);
            $case->save();
        }

        $this->line('Completed all nine Richmond I-95 Palestine-solidarity defendants, including the previously missing Kemp Walker Barber.');
    }

    private function addNewarkJournalists(): void
    {
        $people = [
            [
                'slug' => 'chuck-modiano',
                'name' => 'Chuck Modiano',
                'first' => 'Chuck',
                'last' => 'Modiano',
                'gender' => 'Male',
                'affiliation' => ['Independent press', 'WPFW'],
                'source' => 'https://pressfreedomtracker.us/all-incidents/reporter-slammed-to-the-ground-arrested-at-new-jersey-immigration-protest/',
                'detail' => 'Police grabbed Modiano from behind, slammed him to the ground, and left him in painfully tight zip-tie restraints for roughly four hours despite his press vest and repeated identification as a journalist.',
            ],
            [
                'slug' => 'theoren-papp',
                'name' => 'Theoren Papp',
                'first' => 'Theoren',
                'last' => 'Papp',
                'gender' => 'Male',
                'affiliation' => ['Level 12 Productions'],
                'source' => 'https://pressfreedomtracker.us/all-incidents/videographer-thrown-arrested-at-new-jersey-immigration-protest/',
                'detail' => 'Officers refused to accept Papp’s Level 12 Media badge, dragged and threw him to the ground, and kept him in tight zip-tie restraints for about four hours while damaging or losing reporting equipment.',
            ],
            [
                'slug' => 'yaakov-strasberg',
                'name' => 'Yaakov Strasberg',
                'first' => 'Yaakov',
                'last' => 'Strasberg',
                'gender' => 'Male',
                'affiliation' => ['Independent press'],
                'source' => 'https://pressfreedomtracker.us/all-incidents/independent-reporter-arrested-punched-by-police-at-new-jersey-protest/',
                'detail' => 'Police refused to recognize Strasberg as press, forced him to the ground, punched him in the face, denied medical care for hours, and confiscated reporting equipment that remained missing after his release.',
            ],
            [
                'slug' => 'anita-wise',
                'name' => 'Anita Wise',
                'first' => 'Anita',
                'last' => 'Wise',
                'gender' => 'Female',
                'affiliation' => ['Freelance press', 'Storyful'],
                'source' => 'https://pressfreedomtracker.us/all-incidents/video-producer-thrown-to-ground-arrested-while-covering-new-jersey-protest/',
                'detail' => 'Police dismissed Wise’s identification as a journalist, threw her to the ground, cut a camera strap to remove her equipment, and held her for more than 20 hours in an overcrowded cell.',
            ],
        ];

        $institution = Institution::firstOrCreate(
            ['name' => 'Essex County Correctional Facility'],
            ['city' => 'Newark', 'state' => 'New Jersey']
        );

        foreach ($people as $values) {
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $values['slug']]);
            $prisoner->fill([
                'name' => $values['name'],
                'first_name' => $values['first'],
                'last_name' => $values['last'],
                'gender' => $values['gender'],
                'state' => 'New Jersey',
                'era' => '2020s',
                'ideologies' => ['Press freedom', 'Immigrant rights'],
                'affiliation' => $values['affiliation'],
                'in_custody' => false,
                'released' => true,
                'awaiting_trial' => true,
                'under_review' => false,
                'description' => $values['name'].' is a journalist arrested while documenting protests over conditions at the Delaney Hall immigration detention facility in Newark, New Jersey, on May 31, 2026. Police kettled demonstrators and reporters shortly before the city’s curfew, then allowed only journalists whose credentials they accepted to leave. '.$values['detail'].' Authorities took the arrested journalists to the Essex County Correctional Facility and released them on June 1. Prosecutors dropped initial rioting and resisting-arrest counts on June 8 but retained a failure-to-disperse charge.',
                'body' => $this->sources([
                    ['U.S. Press Freedom Tracker — arrest, custody, force, and charge history', $values['source']],
                ]),
            ]);
            $prisoner->save();

            $case = PrisonerCase::firstOrNew([
                'prisoner_id' => $prisoner->id,
                'charges' => 'Failure to disperse while reporting on the Delaney Hall immigration-detention protest; initial rioting and resisting-arrest charges were dismissed',
            ]);
            $case->institution_id = $institution->id;
            $case->convicted = 'No — failure-to-disperse charge pending in the latest cited report';
            $case->documented_imprisoned_for_days = 1;
            $case->setPartialDate('arrest_date', 2026, 5, 31);
            $case->setPartialDate('incarceration_date', 2026, 5, 31);
            $case->setPartialDate('release_date', 2026, 6, 1);
            $case->save();
        }

        $this->line('Added the four journalists jailed overnight while covering the May 31, 2026 Delaney Hall protest.');
    }

    private function addMichiganEight(): void
    {
        $people = [
            ['Zainab Aliasgar Hakim', 'Zainab', 'Aliasgar', 'Hakim', 'Female', 2003, '2026-06-10', '2026-06-12', 'Conspiracy to transmit threats in interstate and foreign commerce; conspiracy to tamper with a witness'],
            ['Amatullah Aliasgar Hakim', 'Amatullah', 'Aliasgar', 'Hakim', 'Female', 2005, null, null, 'Conspiracy to transmit threats in interstate and foreign commerce'],
            ['Paige Elizabeth Feyock', 'Paige', 'Elizabeth', 'Feyock', 'Female', 2000, '2026-06-10', '2026-06-12', 'Conspiracy to transmit threats in interstate and foreign commerce; conspiracy to tamper with a witness'],
            ['Ahmet Kerem Korkaya', 'Ahmet', 'Kerem', 'Korkaya', 'Male', 1998, '2026-06-10', '2026-06-15', 'Conspiracy to transmit threats in interstate and foreign commerce'],
            ['Jonathan Hongru Zou', 'Jonathan', 'Hongru', 'Zou', 'Male', 2004, '2026-06-10', '2026-06-12', 'Conspiracy to transmit threats in interstate and foreign commerce'],
            ['Alexander Matthew Sepulveda', 'Alexander', 'Matthew', 'Sepulveda', 'Male', 2003, '2026-06-10', '2026-06-15', 'Conspiracy to transmit threats in interstate and foreign commerce; destruction of property to prevent seizure'],
            ['Mariam Muhammed Odeh', 'Mariam', 'Muhammed', 'Odeh', 'Female', 2002, '2026-06-10', null, 'Conspiracy to transmit threats in interstate and foreign commerce'],
            ['Colin Hunter Weger', 'Colin', 'Hunter', 'Weger', 'Male', 2002, '2026-06-10', '2026-06-12', 'Conspiracy to transmit threats in interstate and foreign commerce'],
        ];

        $sources = $this->sources([
            ['U.S. Attorney, Eastern District of Michigan — indictment, full names, ages, and charges', 'https://www.justice.gov/usao-edmi/pr/department-justice-indicts-eight-conspirators-who-threatened-university-michigan'],
            ['Michigan Advance — June 12 bond releases and defense context', 'https://michiganadvance.com/2026/06/12/judge-grants-bond-for-four-pro-palestinian-u-m-students-charged-with-vandalism-transmitting-threats/'],
            ['Michigan Advance — indictment and Palestine-divestment campaign context', 'https://michiganadvance.com/2026/06/10/doj-indicts-8-pro-palestinian-activists-over-threats-tied-to-u-m-divestment-push/'],
        ]);
        $institution = Institution::firstOrCreate(['name' => 'United States Marshals Service custody']);

        foreach ($people as [$name, $first, $middle, $last, $gender, $birthYear, $arrestDate, $releaseDate, $charges]) {
            $slug = str($name)->slug()->toString();
            $existingSlug = match ($name) {
                'Zainab Aliasgar Hakim' => 'zainab-hakim',
                'Jonathan Hongru Zou' => 'jonathan-zou',
                default => $slug,
            };
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $existingSlug]);
            $prisoner->fill([
                'name' => $name,
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
                'gender' => $gender,
                'state' => 'Michigan',
                'era' => '2020s',
                'ideologies' => ['Pro-Palestine', 'Student activism'],
                'affiliation' => ['University of Michigan divestment movement', 'Michigan Eight'],
                'in_custody' => false,
                'released' => true,
                'awaiting_trial' => true,
                'under_review' => false,
                'description' => $name.' is one of eight Palestine-solidarity activists federally indicted in May 2026 over a University of Michigan divestment campaign. The indictment, unsealed June 10, accuses the group of conspiring to transmit threats against university officials, law-enforcement officials, businesses, and the Jewish Federation of Metropolitan Detroit through protests, messages, and property damage. Civil-rights advocates argued that the charging document blurred alleged unlawful acts with protected political speech. The charges remain allegations, and the federal case is pending.',
                'body' => $sources,
            ]);
            $prisoner->setPartialDate('birthdate', $birthYear, null, null, true);
            $prisoner->save();

            $case = $prisoner->cases()->first() ?? new PrisonerCase(['prisoner_id' => $prisoner->id]);
            $case->charges = $charges;
            $case->indicted = 'Federal indictment filed May 20, 2026 and unsealed June 10, 2026 in United States v. Hakim et al., No. 5:26-cr-20306 (E.D. Mich.)';
            $case->convicted = 'No — charges pending';
            $case->plead = $name === 'Amatullah Aliasgar Hakim' ? null : 'Not guilty';
            $case->prosecutor = 'U.S. Attorney Jerome F. Gorgon Jr., Eastern District of Michigan';
            if ($arrestDate) {
                [$year, $month, $day] = array_map('intval', explode('-', $arrestDate));
                $case->institution_id = $institution->id;
                $case->setPartialDate('arrest_date', $year, $month, $day);
                $case->setPartialDate('incarceration_date', $year, $month, $day);
            }
            if ($releaseDate) {
                [$year, $month, $day] = array_map('intval', explode('-', $releaseDate));
                $case->setPartialDate('release_date', $year, $month, $day);
            }
            $case->save();
        }

        $this->line('Completed the Michigan Eight set, adding six missing defendants and correcting the two existing profiles.');
    }

    private function addKadeByrand(): void
    {
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'kade-wilde-byrand']);
        $prisoner->fill([
            'name' => 'Kade Wilde Byrand',
            'first_name' => 'Kade',
            'middle_name' => 'Wilde',
            'last_name' => 'Byrand',
            'gender' => 'Male',
            'state' => 'California',
            'era' => '2020s',
            'ideologies' => ['Immigrant rights'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Kade Wilde Byrand joined a June 2025 demonstration in downtown Los Angeles against federal immigration raids. The federal complaint accused him of pushing a Bureau of Prisons officer outside the Metropolitan Detention Center; it also cited a social-media post in which he encouraged people to attend the protest. Byrand pleaded guilty on September 17, 2025. On January 30, 2026, the court sentenced him to time served—four days in custody—and a $1,000 fine.',
            'body' => $this->sources([
                ['The Guardian — protest context, accusation, and First Amendment concerns', 'https://www.theguardian.com/us-news/2025/jun/13/protester-charged-ice-los-angeles'],
                ['Federal Public Defender case list — plea and January 30, 2026 sentence', 'https://s3.documentcloud.org/documents/26878587/list-of-defendants-february-2026.pdf'],
                ['Central District of California calendar — January 30, 2026 sentencing', 'https://apps.cacd.uscourts.gov/JpsApi/file/5df152a9-3465-4f0e-909f-08de5f54bee6'],
            ]),
        ]);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Assaulting, resisting, or impeding a federal officer outside the Metropolitan Detention Center in Los Angeles',
        ]);
        $case->convicted = 'Yes — pleaded guilty September 17, 2025';
        $case->plead = 'Guilty on September 17, 2025';
        $case->sentence = 'Time served of four days in custody and a $1,000 fine';
        $case->documented_imprisoned_for_days = 4;
        $case->setPartialDate('arrest_date', 2025, 6, 11);
        $case->setPartialDate('incarceration_date', 2025, 6, 11);
        $case->setPartialDate('release_date', 2025, 6);
        $case->setPartialDate('sentenced_date', 2026, 1, 30);
        $case->save();

        $this->line('Added Kade Wilde Byrand with the completed plea and sentence history.');
    }

    private function updateCopCityFlyerDefendants(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Bartow County Jail'],
            ['city' => 'Cartersville', 'state' => 'Georgia']
        );
        $sources = $this->sources([
            ['The Guardian — defendants, arrest, solitary confinement, and bond history', 'https://www.theguardian.com/us-news/2023/may/13/cop-city-activists-arrests-georgia-law'],
            ['The Intercept — flyer case and first four nights in solitary confinement', 'https://theintercept.com/2023/05/02/cop-city-activists-arrest-flyers/'],
            ['Associated Press — continuing case status before the RICO dismissal', 'https://apnews.com/article/be5ef1ed1951a73870656f61fbbc567b'],
            ['Associated Press — December 30, 2025 dismissal of the 61-person RICO indictment', 'https://apnews.com/article/d72ff2df1c4b25b3d99f7260716e60aa'],
        ]);

        $existing = [
            'julia-dupuis' => [
                'description' => 'Julia Dupuis was arrested in Cartersville, Georgia, on April 28, 2023 with Caroline “Charley” Tennenbaum and Abeeku Osei Vassall after activists placed flyers on neighborhood mailboxes identifying a Georgia State Patrol trooper involved in the killing of Stop Cop City activist Manuel “Tortuguita” Paez Terán. Authorities charged all three with misdemeanor stalking and felony intimidation of a state officer. Dupuis spent the first four nights in solitary confinement at Bartow County Jail and was released on bond in May. Georgia later included Dupuis in its 61-person Stop Cop City racketeering indictment; a Fulton County judge dismissed that RICO charge on December 30, 2025. The cited ruling did not dispose of the separately filed Bartow County flyer charges.',
                'release' => [2023, 5, null],
                'days' => 4,
            ],
            'charley-tennenbaum' => [
                'description' => 'Caroline “Charley” Tennenbaum was arrested in Cartersville, Georgia, on April 28, 2023 with Julia Dupuis and Abeeku Osei Vassall after activists placed flyers on neighborhood mailboxes identifying a Georgia State Patrol trooper involved in the killing of Stop Cop City activist Manuel “Tortuguita” Paez Terán. Authorities charged all three with misdemeanor stalking and felony intimidation of a state officer. Tennenbaum spent the first four nights in solitary confinement at Bartow County Jail, was denied bond on May 15, and remained jailed for more than seven weeks before release in July. Georgia later included Tennenbaum in its 61-person Stop Cop City racketeering indictment; a Fulton County judge dismissed that RICO charge on December 30, 2025. The cited ruling did not dispose of the separately filed Bartow County flyer charges.',
                'release' => [2023, 7, null],
                'days' => 53,
            ],
        ];

        foreach ($existing as $slug => $values) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                $this->warn($slug.' was not found; skipped correction.');
                continue;
            }
            $prisoner->description = $values['description'];
            $prisoner->body = $sources;
            $prisoner->awaiting_trial = true;
            $prisoner->save();

            $case = $prisoner->cases()->first();
            if ($case) {
                $case->institution_id = $institution->id;
                $case->convicted = 'No — the December 30, 2025 ruling dismissed the later RICO count; the separately filed flyer charges are not shown as resolved by that ruling';
                $case->documented_imprisoned_for_days = $values['days'];
                $case->setPartialDate('release_date', ...$values['release']);
                $case->save();
            }
        }

        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'abeeku-osei-vassall']);
        $prisoner->fill([
            'name' => 'Abeeku Osei Vassall',
            'first_name' => 'Abeeku',
            'middle_name' => 'Osei',
            'last_name' => 'Vassall',
            'state' => 'Georgia',
            'era' => '2020s',
            'ideologies' => ['Stop Cop City', 'Environmental activism', 'Police accountability'],
            'affiliation' => ['Defend the Atlanta Forest'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'Abeeku Osei Vassall was arrested in Cartersville, Georgia, on April 28, 2023 with Julia Dupuis and Caroline “Charley” Tennenbaum after activists placed flyers on neighborhood mailboxes identifying a Georgia State Patrol trooper involved in the killing of Stop Cop City activist Manuel “Tortuguita” Paez Terán. Authorities charged all three with misdemeanor stalking and felony intimidation of a state officer. Vassall spent the first four nights in solitary confinement at Bartow County Jail. A judge set a $20,000 bond at a May 15 hearing, and supporters covered it. Georgia later included Vassall in its 61-person Stop Cop City racketeering indictment; a Fulton County judge dismissed that RICO charge on December 30, 2025. The cited ruling did not dispose of the separately filed Bartow County flyer charges.',
            'body' => $sources,
        ]);
        $prisoner->setPartialDate('birthdate', 1999, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Felony intimidation of a state officer and misdemeanor stalking for distributing flyers identifying a trooper involved in the killing of Tortuguita',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — the December 30, 2025 ruling dismissed the later RICO count; the separately filed flyer charges are not shown as resolved by that ruling';
        $case->sentence = 'Pretrial detention; $20,000 bond set May 15, 2023 and covered by supporters';
        $case->documented_imprisoned_for_days = 17;
        $case->setPartialDate('arrest_date', 2023, 4, 28);
        $case->setPartialDate('incarceration_date', 2023, 4, 28);
        $case->setPartialDate('release_date', 2023, 5);
        $case->save();

        $this->line('Added Abeeku Osei Vassall and corrected the custody and case status of the three Cop City flyer defendants.');
    }

    private function addEmilyPhillips(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Ramsey County Jail'],
            ['city' => 'Saint Paul', 'state' => 'Minnesota']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'emily-heather-phillips']);
        $prisoner->fill([
            'name' => 'Emily Heather Phillips',
            'first_name' => 'Emily',
            'middle_name' => 'Heather',
            'last_name' => 'Phillips',
            'aka' => 'Red Witch',
            'gender' => 'Female',
            'state' => 'Minnesota',
            'era' => '2020s',
            'ideologies' => ['Immigrant rights', 'Anti-ICE'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Emily Heather Phillips was arrested across the street from Cities Church in Saint Paul on Easter Sunday, April 5, 2026, during an anti-ICE demonstration. Protesters targeted the church because one of its pastors, David Easterwood, was also the acting director of ICE’s Saint Paul field office. Police accused Phillips of disrupting the service after warnings about amplified sound; Phillips and her lawyer said she had turned off the equipment and was speaking with her unamplified voice on public property. She spent the night in Ramsey County Jail. At her April 6 appearance, Ramsey County District Judge Maria Mitchel found no probable cause, dismissed all four misdemeanor counts, and ordered her release.',
            'body' => $this->sources([
                ['Minnesota Star Tribune — arrest, overnight detention, charges, and April 6 dismissal', 'https://www.startribune.com/protester-arrested-at-st-paul-cities-church-easter/601661319'],
                ['Minnesota Spokesman-Recorder — Ramsey County Jail release and Phillips’s account', 'https://spokesman-recorder.com/2026/04/07/emily-phillips-cities-church-arrest-charges-dismissed/'],
                ['CBS Minnesota — April 5 arrest and April 6 court ruling', 'https://www.cbsnews.com/minnesota/news/st-paul-cities-church-protest-arrest-easter-sunday/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1992, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Four misdemeanor counts, including disorderly conduct and interference with a religious observance, arising from an anti-ICE demonstration outside Cities Church',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — all charges dismissed April 6, 2026 after the judge found no probable cause';
        $case->sentence = 'Held overnight in Ramsey County Jail and released after the first court appearance';
        $case->documented_imprisoned_for_days = 1;
        $case->judge = 'Ramsey County District Judge Maria Mitchel';
        $case->setPartialDate('arrest_date', 2026, 4, 5);
        $case->setPartialDate('incarceration_date', 2026, 4, 5);
        $case->setPartialDate('release_date', 2026, 4, 6);
        $case->save();

        $this->line('Added Emily Heather Phillips with her overnight detention and dismissal.');
    }

    private function addRogelioBolufe(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'U.S. Immigration and Customs Enforcement custody'],
            ['state' => 'United States']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'rogelio-bolufe-izquierdo']);
        $prisoner->fill([
            'name' => 'Rogelio Enrique Bolufé Izquierdo',
            'first_name' => 'Rogelio',
            'middle_name' => 'Enrique',
            'last_name' => 'Bolufé Izquierdo',
            'aka' => 'Rogelio Bolufé',
            'gender' => 'Male',
            'state' => 'Florida',
            'era' => '2020s',
            'ideologies' => ['Immigrant rights', 'Detainee organizing'],
            'affiliation' => ['Unión de Secuestrados por ICE'],
            'in_custody' => false,
            'released' => true,
            'in_exile' => true,
            'currently_in_exile' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Rogelio Enrique Bolufé Izquierdo, a Cuban and Ecuadorian national living in Florida, entered ICE custody on August 18, 2025 after a Florida drug-possession arrest; that criminal charge was later dropped. ICE held him for nearly ten months and transferred him through detention sites in Florida, Texas, New Mexico, Alabama, Arizona, and Washington. While detained, Bolufé filed legal challenges, protested detention conditions, organized hunger strikes, and helped organize the Unión de Secuestrados por ICE. He and advocates described the repeated transfers as retaliation for those activities. On June 1, 2026, while an immigration appeal remained pending, the United States deported him to Guayaquil, Ecuador. ICE did not confirm that the deportation was connected to his organizing.',
            'body' => $this->sources([
                ['U.S. District Court for the District of New Mexico — August 18, 2025 ICE custody and immigration proceedings', 'https://law.justia.com/cases/federal/district-courts/new-mexico/nmdce/1:2026cv01117/553054/4/'],
                ['Source New Mexico — hunger strike, detainee organizing, transfers, and dropped Florida charge', 'https://sourcenm.com/2026/05/08/nm-ice-detainee-says-he-was-subject-to-sudden-transfer-poor-conditions-amid-10-day-hunger-strike/'],
                ['The American Prospect — detainee union and reported retaliation culminating in deportation', 'https://prospect.org/2026/06/12/feds-deport-ice-detainee-organizer-to-ecuador/'],
                ['Martí Noticias — full name and June 1, 2026 deportation date', 'https://www.martinoticias.com/a/eeuu-deport%C3%B3-a-ecuador-a-exoficial-cubano-rogelio-boluf%C3%A9/469076.html'],
                ['Telemundo 51 — Bolufé’s account of the June 1 deportation to Ecuador', 'https://www.telemundo51.com/noticias/local/rogelio-bolufe-explica-si-su-deportacion-de-eeuu-tuvo-que-ver-con-su-relacion-con-la-familia-castro/2796489/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1981, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Civil immigration detention after an August 2025 Florida drug-possession arrest; the Florida criminal charge was later dropped',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — the Florida drug-possession charge was dropped; held in civil immigration custody';
        $case->sentence = 'Held in ICE custody for 287 days, during which he organized detainee protests and hunger strikes; deported to Ecuador on June 1, 2026 while an immigration appeal was pending';
        $case->documented_imprisoned_for_days = 287;
        $case->setPartialDate('arrest_date', 2025, 8, 17);
        $case->setPartialDate('incarceration_date', 2025, 8, 18);
        $case->setPartialDate('release_date', 2026, 6, 1);
        $case->setPartialDate('in_exile_since', 2026, 6, 1);
        $case->save();

        $this->line('Added Rogelio Bolufé with his ICE detention, organizing, deportation, and exile record.');
    }

    private function addLuisGaleano(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Krome North Service Processing Center'],
            ['city' => 'Miami', 'state' => 'Florida']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'luis-manuel-chavarria-galeano']);
        $prisoner->fill([
            'name' => 'Luis Manuel Chavarría Galeano',
            'first_name' => 'Luis',
            'middle_name' => 'Manuel',
            'last_name' => 'Chavarría Galeano',
            'aka' => 'Luis Galeano; Luis Manuel Chavarría-Galeano',
            'gender' => 'Male',
            'state' => 'Florida',
            'era' => '2020s',
            'ideologies' => ['Press freedom', 'Nicaraguan democracy'],
            'affiliation' => ['Café con Voz', '100% Noticias'],
            'in_custody' => false,
            'released' => true,
            'in_exile' => true,
            'currently_in_exile' => true,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'Luis Manuel Chavarría Galeano, known professionally as Luis Galeano, is a Nicaraguan journalist and director of the radio and internet program Café con Voz. He fled Nicaragua in December 2018 after police raided 100% Noticias and authorities sought his arrest. The Ortega-Murillo government later stripped him of Nicaraguan citizenship and confiscated his property because of his opposition journalism; Spain subsequently granted him citizenship. U.S. immigration authorities arrested Galeano in Orlando, Florida, on September 14, 2026 while he was working as a rideshare driver. He spent twelve days in immigration custody, including confinement at Krome North Service Processing Center, while press-freedom groups warned that deportation could expose him to renewed persecution in Nicaragua. He was released on a $10,000 bond on September 26. His asylum and removal proceedings remain pending, with the next preliminary hearing reported as scheduled for November 4, 2026.',
            'body' => $this->sources([
                ['Committee to Protect Journalists — September 14 arrest and journalism history', 'https://cpj.org/2026/09/cpj-calls-on-ice-to-release-exiled-nicaraguan-journalist-luis-galeano/'],
                ['WLRN — full name, age, Krome custody, and immigration case', 'https://www.wlrn.org/immigration/2026-09-19/exiled-nicaraguan-journalist-detained-by-ice-in-florida-he-could-be-deported-to-country-he-escaped'],
                ['Telemundo — September 26 release on bond', 'https://www.telemundo.com/noticias/noticias-telemundo/inmigracion/ice-libera-bajo-fianza-al-periodista-nicaraguense-luis-galeano-un-crit-rcna599981'],
                ['Reporters Without Borders — Spanish citizenship and danger of deportation', 'https://rsf.org/en/rsf-calls-spanish-government-protect-spanish-journalist-luis-galeano-and-prevent-his-deportation'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1978, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Civil immigration detention and removal proceedings while a long-pending asylum application remained unresolved',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No criminal conviction — immigration proceedings remain pending';
        $case->sentence = 'Held in immigration custody for twelve days and released on a $10,000 bond';
        $case->documented_imprisoned_for_days = 12;
        $case->setPartialDate('arrest_date', 2026, 9, 14);
        $case->setPartialDate('incarceration_date', 2026, 9, 14);
        $case->setPartialDate('release_date', 2026, 9, 26);
        $case->setPartialDate('in_exile_since', 2018, 12);
        $case->save();

        $this->line('Added Luis Galeano with his twelve-day ICE detention and release on bond.');
    }

    private function addJevonMartinez(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Metropolitan Detention Center Bernalillo County'],
            ['city' => 'Albuquerque', 'state' => 'New Mexico']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'jevon-martinez']);
        $prisoner->fill([
            'name' => 'Jevon Martinez',
            'first_name' => 'Jevon',
            'last_name' => 'Martinez',
            'gender' => 'Male',
            'state' => 'New Mexico',
            'era' => '2020s',
            'ideologies' => ['Anti-surveillance', 'Privacy rights'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => true,
            'under_review' => false,
            'description' => 'Jevon Martinez publicly opposed Flock Safety’s automated license-plate-reader network as mass surveillance. New Mexico police first charged him in July 2026 with nine felonies after alleging that he dismantled or damaged several Rio Rancho cameras and left American Civil Peace flags in their place. On September 2, after Bernalillo County ended its Flock contracts, Martinez livestreamed himself removing ten camera systems and drove to the sheriff’s office with the equipment in his truck, saying that he intended to return it. Deputies arrested him outside the office. Prosecutors sought continued detention based partly on the pending Rio Rancho case, but Judge Claire Ann McDaniel ordered his release under supervision on September 4. The property-damage, larceny, evidence-tampering, and related charges remain accusations and were still pending in the cited reports.',
            'body' => $this->sources([
                ['Albuquerque Journal — July charges, political motive, and first court appearance', 'https://www.abqjournal.com/news/rio-rancho-man-charged-in-license-plate-reader-vandalism/3079744'],
                ['City Desk ABQ — September 2 arrest and stated political protest', 'https://citydesk.org/2026/09/03/rio-rancho-man-faces-felony-charges-after-removing-flock-cameras-in-political-protest/'],
                ['KRQE via AOL — September 4 supervised release', 'https://www.aol.com/articles/man-accused-destroying-flock-cameras-225727000.html'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1982, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Criminal damage to property, larceny, tampering with evidence, possession of burglary tools, and related counts for dismantling automated license-plate-reader cameras',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — charges pending';
        $case->sentence = 'Pretrial detention followed by supervised release';
        $case->documented_imprisoned_for_days = 2;
        $case->setPartialDate('arrest_date', 2026, 9, 2);
        $case->setPartialDate('incarceration_date', 2026, 9, 2);
        $case->setPartialDate('release_date', 2026, 9, 4);
        $case->save();

        $this->line('Added Jevon Martinez with his anti-surveillance action, charges, and two-day detention.');
    }

    private function addTarekBazrouk(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Metropolitan Detention Center Brooklyn'],
            ['city' => 'Brooklyn', 'state' => 'New York']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'tarek-bazrouk']);
        $prisoner->fill([
            'name' => 'Tarek Bazrouk',
            'first_name' => 'Tarek',
            'last_name' => 'Bazrouk',
            'gender' => 'Male',
            'state' => 'New York',
            'era' => '2020s',
            'ideologies' => ['Palestine solidarity'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Tarek Bazrouk was a young Palestinian-American student active in New York City demonstrations concerning Israel’s war in Gaza. Federal prosecutors charged him with hate crimes for assaults on three Jewish or pro-Israel counter-demonstrators at protests in April and December 2024 and January 2025. Bazrouk pleaded guilty in June 2025, acknowledging that he selected the victims because of their Jewish religion or Israeli national origin. He had been held without release since his May 7, 2025 arrest. On October 28, 2025, U.S. District Judge Richard Berman sentenced him to seventeen months in prison followed by three years of supervised release. Palestine-solidarity organizations argued that federal intervention in matters already proceeding in state court reflected selective and politically escalated prosecution; more than 12,000 people supported a request for time served. Bazrouk was released on June 23, 2026 after 413 days in custody and completion of an early-release program.',
            'body' => $this->sources([
                ['U.S. Attorney’s Office for the Southern District of New York — offenses, plea, and sentence', 'https://www.justice.gov/usao-sdny/pr/new-york-man-sentenced-17-months-prison-hate-crimes-after-repeatedly-assaulting-jewish'],
                ['Durub — defense campaign account and political-prosecution claim', 'https://www.durub.org/campaigns/prisoner-support/tarek-bazrouk'],
                ['JNS — June 23, 2026 early release and support campaign', 'https://www.jns.org/news/u-s-news/palestinian-american-convicted-of-assaulting-jews-released-early-from-prison'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 2005, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Three federal hate-crime counts for assaults on Jewish or pro-Israel counter-demonstrators at Palestine-related protests in New York City',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'Yes — pleaded guilty in June 2025';
        $case->plead = 'Guilty in June 2025';
        $case->sentence = 'Seventeen months in federal prison followed by three years of supervised release; released early after completing a prison program';
        $case->documented_imprisoned_for_days = 413;
        $case->judge = 'U.S. District Judge Richard M. Berman';
        $case->setPartialDate('arrest_date', 2025, 5, 7);
        $case->setPartialDate('incarceration_date', 2025, 5, 7);
        $case->setPartialDate('sentenced_date', 2025, 10, 28);
        $case->setPartialDate('release_date', 2026, 6, 23);
        $case->save();

        $this->line('Added Tarek Bazrouk with the federal case, 17-month sentence, and June 2026 release.');
    }

    private function addPaulErvinJohnson(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Hennepin County Medical Center'],
            ['city' => 'Minneapolis', 'state' => 'Minnesota']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'paul-ervin-johnson']);
        $prisoner->fill([
            'name' => 'Paul Ervin Johnson',
            'first_name' => 'Paul',
            'middle_name' => 'Ervin',
            'last_name' => 'Johnson',
            'gender' => 'Male',
            'state' => 'Minnesota',
            'era' => '2020s',
            'ideologies' => ['Immigrant rights', 'Anti-ICE'],
            'in_custody' => false,
            'released' => true,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Paul Ervin Johnson was a Minneapolis contractor who documented and protested federal immigration operations during Operation Metro Surge. On January 22, 2026 he followed federal agents in his van and stopped to observe them in a north Minneapolis parking lot. The government alleged that he approached their vehicle with a baseball bat and later sprayed pepper spray; Johnson said masked agents pulled him from the van and beat him unconscious, causing a traumatic brain injury and torn rotator cuff. Agents kept him shackled to a hospital bed for five days without access to his phone. Prosecutors first filed a felony assault complaint and later reduced the case to a misdemeanor. After defense filings challenged the agents’ account and affidavits, the government moved to dismiss. The court dismissed the case with prejudice in July 2026.',
            'body' => $this->sources([
                ['MPR News — January 22 arrest, five-day hospital detention, injuries, and competing accounts', 'https://www.mprnews.org/story/2026/03/20/civilian-case-could-test-use-of-military-prosecutors-in-minnesota-after-ice-surge'],
                ['CBS Minnesota — government dismissal motion and questions concerning agent affidavits', 'https://www.cbsnews.com/minnesota/news/minnesota-protester-assault-cases-dismissed-minnesota/'],
                ['National Association of Criminal Defense Lawyers — docket and dismissal with prejudice', 'https://www.nacdl.org/brief/US-v-Johnson'],
                ['U.S. Attorney for the District of Minnesota — government allegations in the original complaint', 'https://www.justice.gov/usao-mn/pr/sixteen-defendants-charged-violently-assaulting-federal-officers-and-property'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1978, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Assaulting, resisting, or impeding federal officers during observation of an immigration-enforcement operation; later reduced from a felony complaint to a misdemeanor information',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'No — dismissed with prejudice in July 2026';
        $case->sentence = 'Five days shackled in hospital custody before release; prosecution later dismissed with prejudice';
        $case->documented_imprisoned_for_days = 5;
        $case->setPartialDate('arrest_date', 2026, 1, 22);
        $case->setPartialDate('incarceration_date', 2026, 1, 22);
        $case->setPartialDate('release_date', 2026, 1, 27);
        $case->save();

        $this->line('Added Paul Ervin Johnson with his five-day hospital detention and dismissed federal case.');
    }

    private function addSophieRoske(): void
    {
        $institution = Institution::firstOrCreate(
            ['name' => 'Federal Bureau of Prisons'],
            ['state' => 'Federal']
        );
        $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => 'sophie-roske']);
        $prisoner->fill([
            'name' => 'Sophie Roske',
            'first_name' => 'Sophie',
            'last_name' => 'Roske',
            'aka' => ['Nicholas John Roske'],
            'gender' => 'Female',
            'state' => 'California',
            'era' => '2020s',
            'ideologies' => ['Abortion rights', 'Gun control', 'LGBTQ rights'],
            'in_custody' => true,
            'released' => false,
            'awaiting_trial' => false,
            'under_review' => false,
            'description' => 'Sophie Roske, charged under the name Nicholas John Roske, traveled from California to Maryland in June 2022 intending to kill Supreme Court Justice Brett Kavanaugh. Prosecutors said Roske was motivated by the leaked draft of the Dobbs abortion ruling and fears that the Court would loosen gun restrictions and later threaten same-sex marriage. After arriving near Kavanaugh’s home with a pistol and burglary tools, Roske walked away, called 911, disclosed the plan, and surrendered before entering the property or confronting the justice. Roske remained in federal custody from June 8, 2022, pleaded guilty without a plea agreement on April 8, 2025, and was sentenced on October 3, 2025 to 97 months in prison and lifetime supervised release. The Justice Department appealed the sentence as too lenient. Roske remained imprisoned while the appeal was pending in 2026.',
            'body' => $this->sources([
                ['U.S. Department of Justice — guilty plea, offense facts, 97-month sentence, and lifetime supervision', 'https://www.justice.gov/opa/pr/nicholas-roske-sentenced-over-eight-years-prison-attempted-murder-supreme-court-justice'],
                ['Associated Press — April 8, 2025 guilty plea and political motives described in court', 'https://apnews.com/article/25b1055d257b0905f7b7d04a14981530'],
                ['Associated Press — October 3, 2025 sentence, surrender, custody, and transgender identity', 'https://apnews.com/article/3262cca6bdb7c90ada407fbd8944ff7d'],
                ['The Washington Post — June 2026 government appeal seeking a longer sentence', 'https://www.washingtonpost.com/dc-md-va/2026/06/22/she-got-eight-years-plotting-kill-justice-kavanaugh-prosecutors-want-more/'],
            ]),
        ]);
        $prisoner->setPartialDate('birthdate', 1995, null, null, true);
        $prisoner->save();

        $case = PrisonerCase::firstOrNew([
            'prisoner_id' => $prisoner->id,
            'charges' => 'Attempting to assassinate a Justice of the United States Supreme Court',
        ]);
        $case->institution_id = $institution->id;
        $case->convicted = 'Yes — pleaded guilty on April 8, 2025';
        $case->plead = 'Guilty without a plea agreement';
        $case->sentence = '97 months in federal prison followed by lifetime supervised release; government appeal seeking a longer sentence remained pending in 2026';
        $case->judge = 'U.S. District Judge Deborah L. Boardman';
        $case->setPartialDate('arrest_date', 2022, 6, 8);
        $case->setPartialDate('incarceration_date', 2022, 6, 8);
        $case->setPartialDate('sentenced_date', 2025, 10, 3);
        $case->save();

        $this->line('Added Sophie Roske with the 2022 arrest, guilty plea, 97-month sentence, and pending government appeal.');
    }

    private function updatePalestineImmigrationAndExtraditionCases(): void
    {
        $updates = [
            'mahmoud-khalil' => [
                'description' => 'Mahmoud Khalil, a Palestinian-Algerian lawful permanent resident and Columbia University graduate, was a negotiator and spokesperson for the 2024 Gaza solidarity encampment. ICE agents arrested him at his Manhattan apartment building on March 8, 2025 after the secretary of state invoked a rarely used foreign-policy ground for removal based on Khalil’s Palestine advocacy. He was transferred to Louisiana and held for 104 days. A federal judge ordered him released on June 20, 2025. The immigration proceedings continued after his release; in September 2026 the Board of Immigration Appeals rejected his effort to terminate the removal case. Khalil remained free while pursuing further judicial review.',
                'sources' => [
                    ['Associated Press — 104-day detention and June 20, 2025 release', 'https://apnews.com/article/69162d21ab22377b1c1c08cf2c83d6cd'],
                    ['Law360 — September 2026 Board of Immigration Appeals ruling', 'https://www.law360.com/immigration/articles/2530722/board-denies-khalil-s-fallacious-bid-to-end-removal-case'],
                ],
                'days' => 104,
                'arrest' => [2025, 3, 8],
                'release' => [2025, 6, 20],
                'custody' => false,
                'sentence' => 'Immigration detention for 104 days; released by federal court order while removal litigation continued',
            ],
            'mohsen-mahdawi' => [
                'description' => 'Mohsen Mahdawi, a Palestinian lawful permanent resident and Columbia University student organizer, was arrested by immigration agents on April 14, 2025 when he appeared for what he believed was a citizenship interview. The government sought to remove him under a foreign-policy provision based on his Palestine advocacy. A federal judge ordered his release on April 30, 2025 after sixteen days in custody. An immigration judge dismissed the removal charge in February 2026 for failure to authenticate the secretary of state’s memorandum, but the Board of Immigration Appeals reinstated proceedings. On July 21, 2026, the Second Circuit ruled that the district court lacked jurisdiction to grant habeas relief at that stage. Mahdawi remained free while appealing the removal order.',
                'sources' => [
                    ['Second Circuit — July 21, 2026 jurisdiction ruling', 'https://law.justia.com/cases/federal/appellate-courts/ca2/25-1113/25-1113-2026-07-21.html'],
                    ['Associated Press — ruling, removal appeal, and continuing release status', 'https://apnews.com/article/14be7729efb59c6cd33205da55c14740'],
                ],
                'days' => 16,
                'arrest' => [2025, 4, 14],
                'release' => [2025, 4, 30],
                'custody' => false,
                'sentence' => 'Sixteen days in immigration detention; released by federal court order while removal litigation continued',
            ],
            'salah-sarsour' => [
                'description' => 'Salah Salem Sarsour, president of the Islamic Society of Milwaukee and a longtime Palestinian-rights advocate, was arrested by ICE on March 30, 2026 after more than three decades as a lawful permanent resident. The government relied on a foreign-policy removal provision and decades-old Israeli convictions; his lawyers argued that officials targeted his protected speech. A federal judge found that Sarsour raised a substantial First Amendment retaliation claim and ordered his release on June 18 after more than eighty days. On September 30, 2026, an immigration judge found him removable on foreign-policy grounds while rejecting a separate allegation that he had lied to immigration authorities. Sarsour announced that he would appeal.',
                'sources' => [
                    ['Associated Press — March 30 arrest and June 18 release order', 'https://apnews.com/article/e8af11d58d714091c72d65e4186d9d83'],
                    ['Associated Press — September 30, 2026 removal ruling and planned appeal', 'https://apnews.com/article/ef9a50be073b571b4d8abbf49696f349'],
                ],
                'days' => 80,
                'arrest' => [2026, 3, 30],
                'release' => [2026, 6, 18],
                'custody' => false,
                'sentence' => 'More than eighty days in immigration detention; released by federal court order while removal proceedings continued',
            ],
            'fergie-chambers' => [
                'description' => 'James Cox “Fergie” Chambers Jr. is an American communist activist and donor to Palestinian humanitarian and legal-defense causes. Spanish officers arrested him in Ibiza on July 10, 2026 under a United States extradition request alleging money laundering and financial support for organizations the government linked to Hamas. Chambers and his attorneys described the sealed prosecution as political retaliation for his Palestine solidarity work. Spain held him at Aranjuez prison near Madrid. On September 22 the Spanish cabinet allowed the extradition request to proceed to the National Court, and Chambers opposed extradition at a September 28 hearing. The court had not issued a final extradition decision as of October 3, 2026.',
                'sources' => [
                    ['The Guardian — arrest, allegations, and political-prosecution concerns', 'https://www.theguardian.com/world/2026/jul/15/james-fergie-chambers-arrest-palestine-aid-trump'],
                    ['The Guardian — September 22 decision allowing the extradition case to proceed', 'https://www.theguardian.com/us-news/2026/sep/22/spain-extradition-request-james-fergie-chambers'],
                    ['EFE — September 28 National Court hearing and opposition to extradition', 'https://efe.com/espana/2026-09-28/filantropo-chambers-jr-citacion-audiencia-nacional/'],
                ],
                'days' => null,
                'arrest' => [2026, 7, 10],
                'release' => null,
                'custody' => true,
                'sentence' => 'Held in Spain pending adjudication of a United States extradition request',
            ],
        ];

        foreach ($updates as $slug => $update) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                continue;
            }
            $prisoner->description = $update['description'];
            $prisoner->body = $this->sources($update['sources']);
            $prisoner->in_custody = $update['custody'];
            $prisoner->released = ! $update['custody'];
            $prisoner->awaiting_trial = $update['custody'];
            $prisoner->save();

            $case = $prisoner->cases()->first();
            if (! $case) {
                continue;
            }
            $case->sentence = $update['sentence'];
            $case->documented_imprisoned_for_days = $update['days'];
            $case->setPartialDate('arrest_date', ...$update['arrest']);
            $case->setPartialDate('incarceration_date', ...$update['arrest']);
            if ($update['release']) {
                $case->setPartialDate('release_date', ...$update['release']);
            }
            $case->save();
        }

        $this->line('Updated the Khalil, Mahdawi, Sarsour, and Chambers immigration or extradition cases through September 2026.');
    }

    /** @param array<int, array{0: string, 1: string}> $sources */
    private function sources(array $sources): string
    {
        $items = array_map(
            fn (array $source): string => '<li><a href="'.e($source[1]).'" target="_blank" rel="noopener noreferrer">'.e($source[0]).'</a></li>',
            $sources
        );

        return '<h2>Sources</h2><ul>'.implode('', $items).'</ul>';
    }
}
