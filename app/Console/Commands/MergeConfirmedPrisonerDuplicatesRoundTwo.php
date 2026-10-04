<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\CalendarEntry;
use App\Models\PodcastEpisode;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class MergeConfirmedPrisonerDuplicatesRoundTwo extends Command
{
    protected $signature = 'prisoners:merge-confirmed-duplicates-round-two';

    protected $description = 'Merge the second researched batch of confirmed duplicate prisoner profiles';

    /** @var array<int, array{0:string, 1:string}> */
    private array $pairs = [
        ['jose-felan', 'jose-angel-felan'],
        ['gerald-de-cessa', 'gerald-decessa'],
        ['dion-diamond', 'dion-diamond-2'],
        ['lucy-gwynne-branham', 'lucy-g-branham'],
        ['naomi-destiny-isaac', 'naomi-nomi-isaac'],
        ['walter-edward-fauntroy', 'walter-fauntroy'],
        ['joyce-ellwanger', 'joyce-elwanger'],
        ['william-breckenridge', 'william-brackenridge'],
        ['james-divins', 'james-divine'],
        ['joseph-mccolgan', 'joseph-mccolgin'],
        ['henry-howe', 'lt-henry-howe'],
        ['benjamin-lowell-careathers', 'benjamin-careathers'],
        ['adolf-wisnesski', 'adolf-wisnefsky'],
        ['richard-m-ring', 'rich-ring'],
        ['benjamin-song', 'benjamin-hanil-song'],
        ['paige-feyock', 'paige-elizabeth-feyock'],
        ['colin-weger', 'colin-hunter-weger'],
        ['anika-d-cunningham', 'anika-cunningham'],
        ['edward-smith', 'edward-naed-smith'],
        ['raphael-joseph', 'raphael-kwesi-joseph'],
        ['david-paul-obrien', 'david-obrien'],
        ['emmett-calvin-brown', 'emmett-brown'],
        ['frank-big-black-smith', 'frank-smith'],
        ['jose-jacques-medina', 'jose-medina'],
        ['clayton-j-woodworth', 'c-j-woodworth'],
        ['dino-butler', 'darrelle-butler'],
        ['dora-kelly-lewis', 'dora-lewis'],
        ['eduardo-herrera', 'eduardo-herera'],
        ['grover-mccorvey', 'grover-mccovey'],
        ['marie-haydee-beltran-torres', 'haydee-beltran-torres'],
        ['howard-wilbur-moore', 'howard-moore'],
        ['lynn-fredriksson', 'lynn-frederiksson'],
        ['mayer-libson-nehring', 'mayer-l-nehring'],
        ['nadarasa-yogarasa', 'nadarasa-yograrasa'],
        ['roberto-jose-maldonado-rivera', 'roberto-jose-maldonado'],
        ['steven-james-murphy', 'steve-murphy'],
        ['thomas-quinn', 'tom-quinn'],
        ['william-hightower', 'w-m-hightower'],
        ['yvette-kelley', 'yvette-kelly'],
    ];

    public function handle(): int
    {
        DB::transaction(function (): void {
            $this->restoreCanonicalSlugs();

            foreach ($this->pairs as [$keepSlug, $dropSlug]) {
                $keep = Prisoner::withUnderReview()->where('slug', $keepSlug)->first();
                if (! $keep) {
                    throw new \RuntimeException("Canonical profile {$keepSlug} was not found.");
                }

                $drop = Prisoner::withUnderReview()->where('slug', $dropSlug)->first();
                if (! $drop) {
                    $this->line("Already merged or absent: {$dropSlug}");
                    continue;
                }

                $this->prepareCases($keep, $drop);
                $this->mergeProfileFields($keep, $drop);
                $this->applyProfileCorrections($keep);

                PodcastEpisode::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);
                CalendarEntry::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);

                PrisonerCase::where('prisoner_id', $drop->id)->delete();
                $drop->delete();
                $this->info("Merged {$drop->name} into {$keep->name}.");
            }
        });

        Cache::forget(PrisonerApiController::cacheKey());

        return self::SUCCESS;
    }

    private function prepareCases(Prisoner $keep, Prisoner $drop): void
    {
        switch ($keep->slug) {
            case 'jose-felan':
                $case = $this->onlyCase($keep);
                $case->charges = 'Arson of multiple St. Paul buildings during the unrest following George Floyd\'s murder';
                $case->sentence = 'Seventy-eight months in federal prison. Felan left prison for a halfway house on December 3, 2025; that date does not mark the end of supervised release or every custody restriction.';
                $case->save();
                break;

            case 'gerald-de-cessa':
                $case = $this->onlyCase($keep);
                $case->sentence = 'Sentenced to fifteen years and held at Fort Leavenworth; the National Civil Liberties Bureau still listed him as confined on March 1, 1919.';
                $case->save();
                break;

            case 'dion-diamond':
                PrisonerCase::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);
                break;

            case 'lucy-gwynne-branham':
                $case = $this->onlyCase($drop);
                $case->prisoner_id = $keep->id;
                $case->sentence = 'Three days in January 1919.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = 3;
                $case->save();
                break;

            case 'naomi-destiny-isaac':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = '2024-06-24';
                $case->documented_imprisoned_for_days = 5;
                $case->imprisoned_for_months = null;
                $case->save();
                break;

            case 'walter-edward-fauntroy':
                $case = $this->onlyCase($keep);
                $case->charges = 'Unlawful entry during the founding Free South Africa Movement sit-in at the South African Embassy';
                $case->arrest_date = '1984-11-21';
                $case->incarceration_date = '1984-11-21';
                $case->release_date = '1984-11-22';
                $case->sentence = 'Held overnight after refusing immediate release; prosecutors later declined the misdemeanor case.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = 1;
                $case->save();
                break;

            case 'joyce-ellwanger':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = null;
                $case->release_date = null;
                $case->documented_imprisoned_for_days = null;
                $case->imprisoned_for_months = 6;
                $case->save();
                break;

            case 'william-breckenridge':
            case 'james-divins':
                $source = $this->onlyCase($drop);
                $case = $this->onlyCase($keep);
                $case->arrest_date = $source->arrest_date;
                $case->incarceration_date = $source->incarceration_date;
                $case->sentenced_date = $source->sentenced_date;
                $case->release_date = $source->release_date;
                $case->documented_imprisoned_for_days = 11;
                $case->imprisoned_for_months = null;
                $case->save();
                break;

            case 'joseph-mccolgan':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = '1990-01-12';
                $case->release_date = '1993-10-01';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'henry-howe':
                $case = $this->onlyCase($keep);
                $case->sentenced_date = '1965-12-22';
                $case->incarceration_date = '1965-12-22';
                $case->release_date = '1966-04-01';
                $case->sentence = 'One year at hard labor after review, dismissal from the Army, and forfeiture of pay; paroled in early April 1966 after serving about three months.';
                $case->date_precision = array_merge((array) $case->date_precision, ['release_date' => 'month']);
                $case->documented_imprisoned_for_days = null;
                $case->imprisoned_for_months = 3;
                $case->save();
                break;

            case 'richard-m-ring':
                $case = $this->onlyCase($keep);
                $case->documented_imprisoned_for_days = 91;
                $case->save();
                break;

            case 'benjamin-song':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '2025-07-15';
                $case->incarceration_date = '2025-07-15';
                $case->save();
                break;

            case 'paige-feyock':
            case 'colin-weger':
                PrisonerCase::where('prisoner_id', $keep->id)->delete();
                PrisonerCase::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);
                break;

            case 'anika-d-cunningham':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = '2006-04-01';
                $case->release_date = '2006-05-01';
                $case->date_precision = array_merge((array) $case->date_precision, [
                    'incarceration_date' => 'month',
                    'release_date' => 'month',
                ]);
                $case->documented_imprisoned_for_days = null;
                $case->imprisoned_for_months = 1;
                $case->save();
                break;

            case 'edward-smith':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = '2006-04-01';
                $case->release_date = '2006-10-06';
                $case->date_precision = array_merge((array) $case->date_precision, ['incarceration_date' => 'month']);
                $case->documented_imprisoned_for_days = null;
                $case->imprisoned_for_months = 6;
                $case->save();
                break;

            case 'david-paul-obrien':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '1966-03-31';
                $case->incarceration_date = null;
                $case->save();
                break;

            case 'frank-big-black-smith':
                $case = $this->onlyCase($drop);
                $case->prisoner_id = $keep->id;
                $case->release_date = null;
                $case->sentence = 'Indicted with the Attica Brothers after the September 1971 uprising; the profile records this separately from the robbery sentence he was already serving.';
                $case->save();
                break;

            case 'dora-kelly-lewis':
                $july = $this->onlyCase($keep);
                $july->documented_imprisoned_for_days = 3;
                $july->save();
                PrisonerCase::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);
                break;

            case 'eduardo-herrera':
                $case = $this->onlyCase($keep);
                $case->incarceration_date = '1930-06-01';
                $case->date_precision = array_merge((array) $case->date_precision, ['incarceration_date' => 'month']);
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'grover-mccorvey':
                $case = $this->onlyCase($keep);
                $case->charges = 'Murder and rioting charges arising from the January 18, 1974 Atmore prison rebellion';
                $case->incarceration_date = null;
                $case->sentence = 'Convicted in the prosecution arising from the Atmore rebellion; the available record does not establish a separate custody span for this case.';
                $case->save();
                break;

            case 'marie-haydee-beltran-torres':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '1980-04-04';
                $case->incarceration_date = '1980-04-04';
                $case->release_date = '2009-04-14';
                $case->sentence = 'Life imprisonment in the New York bombing case, followed by a federal term; released April 14, 2009.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'howard-wilbur-moore':
                $case = $this->onlyCase($keep);
                $case->release_date = '1920-11-24';
                $case->sentence = 'Five years at hard labor; released November 24, 1920, the day before Thanksgiving, as one of the last World War I conscientious objectors still confined.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'lynn-fredriksson':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '1993-12-07';
                $case->charges = 'Destruction of government property and conspiracy in the Pax Christi–Spirit of Life Plowshares action at Seymour Johnson Air Force Base';
                $case->save();
                break;

            case 'mayer-libson-nehring':
                PrisonerCase::where('prisoner_id', $drop->id)->update(['prisoner_id' => $keep->id]);
                break;

            case 'nadarasa-yogarasa':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '2006-08-19';
                $case->incarceration_date = '2006-08-19';
                $case->release_date = '2018-10-29';
                $case->sentence = 'Fourteen years in federal prison for attempting and conspiring to provide material support to the LTTE.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'roberto-jose-maldonado-rivera':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '1985-08-30';
                $case->incarceration_date = '1989-08-28';
                $case->release_date = '1994-06-29';
                $case->sentence = 'Five years in federal prison and a $100,000 fine; released in 1994. President Clinton remitted the unpaid fine in 1999.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'steven-james-murphy':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '2009-10-15';
                $case->incarceration_date = '2009-10-15';
                $case->release_date = '2014-02-25';
                $case->sentence = 'Sixty months in federal prison followed by three years of supervised release.';
                $case->imprisoned_for_months = null;
                $case->documented_imprisoned_for_days = null;
                $case->save();
                break;

            case 'william-hightower':
                $case = $this->onlyCase($keep);
                $case->arrest_date = '1931-05-05';
                $case->save();
                break;

            case 'yvette-kelley':
                $case = $this->onlyCase($keep);
                $case->charges = 'RICO conspiracy and related allegations in the New York 8+ prosecution; two weapons-possession counts';
                $case->arrest_date = '1984-10-17';
                $case->incarceration_date = '1984-10-17';
                $case->release_date = null;
                $case->convicted = 'Acquitted of the RICO conspiracy; convicted of two weapons-possession counts';
                $case->sentence = 'Probation and community service after acquittal on the principal RICO conspiracy charge.';
                $case->save();
                break;
        }
    }

    private function mergeProfileFields(Prisoner $keep, Prisoner $drop): void
    {
        $skipDeath = $keep->slug === 'jose-felan';
        foreach (['photo', 'state', 'address', 'lat', 'lng', 'first_name', 'middle_name', 'last_name',
            'race', 'gender', 'birthdate', 'death_date', 'era', 'website', 'twitter', 'facebook',
            'instagram', 'inmate_number', 'body'] as $field) {
            if ($field === 'death_date' && $skipDeath) {
                continue;
            }
            if (($keep->{$field} === null || $keep->{$field} === '') && $drop->{$field} !== null && $drop->{$field} !== '') {
                $keep->{$field} = $drop->{$field};
            }
        }

        $preferLongerDuplicateDescription = in_array($keep->slug, [
            'naomi-destiny-isaac',
            'richard-m-ring',
            'paige-feyock',
            'colin-weger',
            'grover-mccorvey',
        ], true);
        if (($keep->description === null || $keep->description === '')
            || ($preferLongerDuplicateDescription && mb_strlen((string) $drop->description) > mb_strlen((string) $keep->description))) {
            $keep->description = $drop->description;
        }

        foreach (['ideologies', 'affiliation'] as $field) {
            $keep->{$field} = collect((array) $keep->{$field})
                ->merge((array) $drop->{$field})
                ->filter()
                ->unique(fn ($value) => mb_strtolower((string) $value))
                ->values()
                ->all();
        }

        foreach (['in_custody', 'released', 'in_exile', 'currently_in_exile', 'awaiting_trial', 'minor_case'] as $field) {
            $keep->{$field} = (bool) $keep->{$field} || (bool) $drop->{$field};
        }

        $keep->date_precision = array_replace((array) $drop->date_precision, (array) $keep->date_precision);

        $aliases = collect(preg_split('/\s*[\/;|]\s*/', (string) $keep->aka))
            ->merge(preg_split('/\s*[\/;|]\s*/', (string) $drop->aka))
            ->push($drop->name)
            ->map(fn ($alias) => trim((string) $alias))
            ->filter()
            ->reject(fn ($alias) => mb_strtolower($alias) === mb_strtolower($keep->name))
            ->unique(fn ($alias) => mb_strtolower($alias))
            ->values()
            ->all();
        $keep->aka = implode(' / ', $aliases);
        $keep->save();
    }

    private function applyProfileCorrections(Prisoner $keep): void
    {
        switch ($keep->slug) {
            case 'jose-felan':
                $keep->death_date = null;
                break;
            case 'naomi-destiny-isaac':
                $keep->aka = 'Nomi Isaac';
                break;
            case 'benjamin-song':
                $keep->name = 'Benjamin Hanil Song';
                $keep->middle_name = 'Hanil';
                $keep->aka = 'Champagne';
                break;
            case 'paige-feyock':
                $keep->name = 'Paige Elizabeth Feyock';
                $keep->middle_name = 'Elizabeth';
                $keep->aka = 'Paige Feyock';
                $keep->awaiting_trial = true;
                break;
            case 'colin-weger':
                $keep->name = 'Colin Hunter Weger';
                $keep->middle_name = 'Hunter';
                $keep->aka = 'Colin Weger';
                $keep->awaiting_trial = true;
                break;
            case 'edward-smith':
                $keep->name = 'Edward “Naed” Smith';
                $keep->aka = 'Naed Smith';
                break;
            case 'raphael-joseph':
                $keep->aka = 'Kwesi / Raphael Kwesi Joseph';
                break;
            case 'dora-kelly-lewis':
                $keep->birthdate = '1862-10-13';
                $keep->death_date = '1928-01-31';
                break;
            case 'dino-butler':
                $keep->name = 'Darrelle Dean “Dino” Butler';
                $keep->first_name = 'Darrelle';
                $keep->middle_name = 'Dean';
                $keep->last_name = 'Butler';
                $keep->aka = 'Dino Butler';
                break;
            case 'grover-mccorvey':
                $keep->aka = 'Grover McCovey / Grover McGorvey / Sitting Bull';
                break;
            case 'marie-haydee-beltran-torres':
                $keep->birthdate = '1955-06-27';
                $keep->date_precision = array_merge((array) $keep->date_precision, ['birthdate' => 'day']);
                break;
            case 'howard-wilbur-moore':
                $keep->death_date = '1993-06-09';
                $keep->date_precision = array_merge((array) $keep->date_precision, ['death_date' => 'day']);
                break;
            case 'roberto-jose-maldonado-rivera':
                $keep->birthdate = '1936-01-01';
                $keep->inmate_number = '03588-069';
                $keep->date_precision = array_merge((array) $keep->date_precision, ['birthdate' => 'year']);
                break;
            case 'steven-james-murphy':
                $keep->birthdate = '1966-09-03';
                $keep->inmate_number = '39013-177';
                $keep->date_precision = array_merge((array) $keep->date_precision, ['birthdate' => 'day']);
                break;
            case 'william-hightower':
                $keep->aka = 'W. M. Hightower';
                break;
        }

        $keep->save();
    }

    private function onlyCase(Prisoner $prisoner): PrisonerCase
    {
        return PrisonerCase::where('prisoner_id', $prisoner->id)->firstOrFail();
    }

    private function restoreCanonicalSlugs(): void
    {
        $canonicalSlugs = [
            'Benjamin Hanil Song' => 'benjamin-song',
            'Paige Elizabeth Feyock' => 'paige-feyock',
            'Colin Hunter Weger' => 'colin-weger',
            'Edward “Naed” Smith' => 'edward-smith',
            'Darrelle Dean “Dino” Butler' => 'dino-butler',
        ];

        foreach ($canonicalSlugs as $name => $slug) {
            if (DB::table('prisoners')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('prisoners')->where('name', $name)->update(['slug' => $slug]);
        }
    }
}
