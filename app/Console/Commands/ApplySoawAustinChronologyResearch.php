<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Institution;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ApplySoawAustinChronologyResearch extends Command
{
    private const SOURCE = 'https://www.soaw-austin.org/POC1983_2012.php';

    protected $signature = 'prisoners:apply-soaw-austin-chronology';
    protected $description = 'Apply prisoner, case, and portrait findings from the SOAW Austin chronology';

    public function handle(): int
    {
        DB::transaction(function (): void {
            $this->addMissingProfiles();
            $this->correctCases();
            $this->correctJeromeZawada();
            $this->correctRobertChantal();
            $this->installPortraits();
        });
        Cache::forget(PrisonerApiController::cacheKey());
        $this->info('Applied the SOAW Austin chronology research.');

        return self::SUCCESS;
    }

    private function addMissingProfiles(): void
    {
        $institution = Institution::firstOrCreate(['name' => 'Federal Bureau of Prisons'], ['state' => 'Federal']);
        $profiles = [
            'rich-ring' => [
                'profile' => ['name' => 'Rich Ring', 'first_name' => 'Rich', 'last_name' => 'Ring', 'gender' => 'Male', 'state' => 'Georgia',
                    'description' => 'Rich Ring was an Atlanta human-rights investigator who joined the November 2001 nonviolent civil-disobedience action at Fort Benning calling for the closure of the U.S. Army School of the Americas, later renamed WHINSEC. Ring had previously worked as an ecologist with The Nature Conservancy and at the St. Martin de Porres Catholic Worker house. A federal court sentenced him to three months in prison and a $500 fine for crossing onto the base.'],
                'case' => ['sentence' => 'Three months in federal prison and a $500 fine', 'months' => 3, 'arrest' => [2001, 11, null]],
            ],
            'joyce-ellwanger' => [
                'profile' => ['name' => 'Joyce Ellwanger', 'first_name' => 'Joyce', 'last_name' => 'Ellwanger', 'gender' => 'Female', 'state' => 'Wisconsin', 'birth' => [1937, null, null],
                    'description' => 'Joyce Ellwanger was a retired Milwaukee peace activist who crossed onto Fort Benning during the November 2002 School of the Americas Watch vigil. The action called for the closure of the U.S. Army school whose graduates had been implicated in killings, torture, and other abuses in Latin America. On January 28, 2003, a federal court sentenced Ellwanger to six months in prison.'],
                'case' => ['sentence' => 'Six months in federal prison, with self-reporting', 'months' => 6, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 1, 28]],
            ],
            'al-simmons-soa' => [
                'profile' => ['name' => 'Al Simmons', 'first_name' => 'Al', 'last_name' => 'Simmons', 'gender' => 'Male', 'state' => 'Virginia',
                    'description' => 'Al Simmons, a Richmond, Virginia peace activist, was one of six people prosecuted after demonstrators carried the November 2008 School of the Americas Watch protest onto Fort Benning. The group called for the closure of the U.S. Army School of the Americas/WHINSEC and a change in U.S. policy toward Latin America. On January 26, 2009, Simmons was convicted of entering the military base and sentenced to two months in federal prison.'],
                'case' => ['sentence' => 'Two months in federal prison', 'months' => 2, 'arrest' => [2008, 11, 23], 'sentenced' => [2009, 1, 26]],
            ],
        ];

        foreach ($profiles as $slug => $data) {
            $birth = $data['profile']['birth'] ?? null;
            unset($data['profile']['birth']);
            $prisoner = Prisoner::withUnderReview()->firstOrNew(['slug' => $slug]);
            $prisoner->fill(array_merge($data['profile'], [
                'era' => '2000s', 'ideologies' => ['Anti-war', 'Human rights'],
                'affiliation' => ['School of the Americas Watch'], 'in_custody' => false,
                'released' => true, 'awaiting_trial' => false, 'under_review' => false,
                'body' => $this->sourceHtml(),
            ]));
            if ($birth) {
                $prisoner->setPartialDate('birthdate', ...$birth);
            }
            $prisoner->save();
            $case = PrisonerCase::firstOrNew([
                'prisoner_id' => $prisoner->id,
                'charges' => 'Federal trespass during a School of the Americas Watch vigil at Fort Benning, Georgia',
            ]);
            $case->institution_id = $institution->id;
            $case->convicted = 'Yes';
            $this->applyCase($case, $data['case']);
        }
    }

    private function correctCases(): void
    {
        $updates = [
            'michaele-pasquale' => [
                'profile' => ['name' => 'Mike Pasquale', 'first_name' => 'Mike', 'last_name' => 'Pasquale', 'description' => 'Mike Pasquale was a Syracuse peace activist and program director at the Family Center who crossed onto Fort Benning on November 18, 2001 during a School of the Americas Watch protest. He was convicted of federal trespass on July 12, 2002 and sentenced to six months in prison and a $1,000 fine. Pasquale served the sentence at Federal Prison Camp Allenwood from September 10, 2002 to March 7, 2003.'],
                'case' => ['sentence' => 'Six months in federal prison and a $1,000 fine', 'days' => 178, 'arrest' => [2001, 11, 18], 'sentenced' => [2002, 7, 12], 'incarceration' => [2002, 9, 10], 'release' => [2003, 3, 7]],
                'sources' => [['Prison Notes — exact custody dates', 'https://www.peacecouncil.net/pnls/03/721/721_PrisonNotes.htm'], ['National Catholic Reporter — July 12, 2002 sentencing', 'https://natcath.org/NCR_Online/archives2/2002c/080202/080202t.htm']],
            ],
            'abigail-miller' => ['profile' => ['aka' => 'Abi Miller', 'description' => 'Abigail “Abi” Miller was a Harrisonburg, Virginia community organizer who crossed onto Fort Benning during the November 2001 School of the Americas Watch vigil. A federal court sentenced her to three months in prison and a $500 fine.'], 'case' => ['sentence' => 'Three months in federal prison and a $500 fine', 'months' => 3, 'arrest' => [2001, 11, null], 'incarceration' => null, 'release' => null]],
            'corbin-street' => ['profile' => ['name' => 'Corbin Streett', 'first_name' => 'Corbin', 'last_name' => 'Streett', 'birth' => [1979, null, null], 'description' => 'Corbin Streett was a Mississippi peace activist and rehabilitation-program worker who crossed onto Fort Benning during the November 2002 School of the Americas Watch vigil. On February 10, 2003, Streett was sentenced to three months in federal prison and a $1,000 fine.'], 'case' => ['sentence' => 'Three months in federal prison and a $1,000 fine, with self-reporting', 'months' => 3, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 2, 10], 'incarceration' => null, 'release' => null]],
            'mimi-lavalley' => ['profile' => ['name' => 'Michelle LaValley', 'first_name' => 'Michelle', 'last_name' => 'LaValley', 'aka' => 'Mimi LaValley', 'birth' => [1981, null, null], 'description' => 'Michelle “Mimi” LaValley was a Unitarian Universalist youth-program worker from Jamaica Plain, Massachusetts who crossed onto Fort Benning during the November 2002 School of the Americas Watch vigil. On February 11, 2003, she was sentenced to three months in federal prison and a $1,000 fine.'], 'case' => ['sentence' => 'Three months in federal prison and a $1,000 fine, with self-reporting', 'months' => 3, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 2, 11], 'incarceration' => null, 'release' => null]],
            'lee-mickey' => ['profile' => ['name' => 'Evalee “Lee” Mickey', 'first_name' => 'Evalee', 'last_name' => 'Mickey', 'aka' => 'Lee Mickey', 'birth' => [1935, null, null], 'description' => 'Evalee “Lee” Mickey was a retired Iowa farmer, widowed homemaker, and church peace activist who crossed onto Fort Benning during the November 2002 School of the Americas Watch vigil. On December 20, 2003, she was sentenced to three months in federal prison.'], 'case' => ['sentence' => 'Three months in federal prison, with self-reporting', 'months' => 3, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 12, 20], 'incarceration' => null, 'release' => null]],
            'mike-wisniewski' => ['profile' => ['name' => 'Michael Wisniewski', 'first_name' => 'Michael', 'last_name' => 'Wisniewski', 'aka' => 'Mike Wisniewski', 'birth' => [1949, null, null], 'description' => 'Michael “Mike” Wisniewski was a Los Angeles-area Catholic Worker community member and peace activist who crossed onto Fort Benning during the November 2002 School of the Americas Watch vigil. On February 10, 2003, he was sentenced to three months in federal prison and a $1,000 fine.'], 'case' => ['sentence' => 'Three months in federal prison and a $1,000 fine, with self-reporting', 'months' => 3, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 2, 10], 'incarceration' => null, 'release' => null]],
            'don-haselfeld' => ['profile' => ['name' => 'Donald Haselfeld', 'first_name' => 'Donald', 'last_name' => 'Haselfeld', 'aka' => 'Don Haselfeld'], 'case' => ['sentence' => 'Six months in federal prison, with self-reporting', 'months' => 6, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 1, 28], 'incarceration' => null, 'release' => null]],
            'fr-jim-hynes' => ['profile' => ['aka' => 'James Hynes', 'birth' => [1945, null, null]], 'case' => ['sentence' => 'Six months in federal prison; sentence began April 8, 2003', 'months' => 6, 'arrest' => [2002, 11, 16], 'sentenced' => [2003, 1, 28], 'incarceration' => [2003, 4, 8], 'release' => null]],
            'donald-w-nelson' => ['profile' => ['name' => 'Don Nelson', 'first_name' => 'Don', 'last_name' => 'Nelson', 'aka' => 'Donald W. Nelson'], 'case' => ['sentence' => 'Three months in federal prison; self-reported in January 2006 and was released April 14, 2006', 'months' => 3, 'arrest' => [2005, 11, null], 'incarceration' => [2006, 1, null], 'release' => [2006, 4, 14]]],
            'fredrick-brancel' => ['profile' => ['name' => 'Fred Brancel', 'first_name' => 'Fred', 'last_name' => 'Brancel', 'aka' => 'Fredrick Brancel'], 'case' => ['sentence' => 'Three months in federal prison; released July 7, 2006', 'months' => 3, 'arrest' => [2005, 11, null], 'incarceration' => null, 'release' => [2006, 7, 7]]],
            'francis-woolever' => ['profile' => ['name' => 'Frank Woolever', 'first_name' => 'Frank', 'last_name' => 'Woolever', 'aka' => 'Francis Woolever'], 'case' => ['sentence' => 'Three months in federal prison; released July 7, 2006', 'months' => 3, 'arrest' => [2005, 11, null], 'incarceration' => null, 'release' => [2006, 7, 7]]],
            'kenneth-f-crowley' => ['profile' => ['aka' => 'Ken Crowley'], 'case' => ['sentence' => 'Six months in federal prison; released October 6, 2006', 'months' => 6, 'arrest' => [2005, 11, null], 'incarceration' => null, 'release' => [2006, 10, 6]]],
            'samuel-foster' => ['profile' => ['name' => 'Sam Foster', 'first_name' => 'Sam', 'last_name' => 'Foster', 'aka' => 'Samuel Foster'], 'case' => ['sentence' => 'Two months in federal prison and a $500 fine; released June 7, 2006', 'months' => 2, 'arrest' => [2005, 11, null], 'incarceration' => null, 'release' => [2006, 6, 7]]],
            'edwin-r-lewinson' => ['profile' => ['name' => 'Edward “Ed” Lewinson', 'first_name' => 'Edward', 'last_name' => 'Lewinson', 'aka' => 'Edwin R. Lewinson; Ed Lewinson'], 'case' => ['sentence' => 'Ninety days in federal prison and a $500 fine; reported to FCI Elkton on April 3, 2008', 'days' => 90, 'arrest' => [2007, 11, null], 'sentenced' => [2008, 1, 28], 'incarceration' => [2008, 4, 3], 'release' => null]],
        ];

        foreach ($updates as $slug => $update) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            if (! $prisoner) {
                $this->warn("Missing {$slug}; skipped.");
                continue;
            }
            $birth = $update['profile']['birth'] ?? null;
            unset($update['profile']['birth']);
            $this->savePreservingSlug($prisoner, $update['profile']);
            if ($birth) {
                $prisoner->setPartialDate('birthdate', ...$birth);
                $prisoner->save();
            }
            $this->appendSources($prisoner, $update['sources'] ?? []);
            if ($case = $prisoner->cases()->first()) {
                $this->applyCase($case, $update['case']);
            }
        }
    }

    private function correctJeromeZawada(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'jerome-zawada')->first();
        if (! $prisoner) return;
        $cases = $prisoner->cases()->orderBy('id')->get();
        if (isset($cases[0])) {
            $this->applyCase($cases[0], ['incarceration' => null, 'release' => null, 'days' => null, 'months' => null]);
        }
        if (isset($cases[1])) {
            $this->applyCase($cases[1], ['sentence' => 'Six months in federal prison', 'arrest' => [2001, 11, null], 'incarceration' => null, 'release' => null, 'days' => null, 'months' => 6]);
        }
        if (isset($cases[2])) {
            $this->applyCase($cases[2], ['sentence' => 'Six months in federal prison; released August 2, 2006', 'arrest' => [2005, 11, null], 'months' => 6]);
        }
        $this->appendSources($prisoner);
    }

    private function correctRobertChantal(): void
    {
        $prisoner = Prisoner::withUnderReview()->where('slug', 'robert-chantal')->first();
        if (! $prisoner) return;
        $this->savePreservingSlug($prisoner, ['name' => 'Robert “Nashua” Chantal', 'first_name' => 'Robert', 'middle_name' => 'Nashua', 'last_name' => 'Chantal', 'aka' => 'Nashua Chantal',
            'description' => 'Robert “Nashua” Chantal was an Americus, Georgia volunteer who provided housing assistance and worked with international youth groups. He crossed onto Fort Benning during the November 2004 School of the Americas Watch vigil and was sentenced to ninety days in federal prison and a $500 fine. He was released June 10, 2005. Chantal later returned to the annual protest and received another federal sentence after a 2012 line-crossing action.']);
        $this->appendSources($prisoner);
        if ($case = $prisoner->cases()->where('sentence', '90 days')->first()) {
            $case->charges = 'Federal trespass during the November 2004 School of the Americas Watch vigil at Fort Benning, Georgia';
            $this->applyCase($case, ['sentence' => 'Ninety days in federal prison and a $500 fine; released June 10, 2005', 'days' => 90, 'arrest' => [2004, 11, 21], 'incarceration' => null, 'release' => [2005, 6, 10]]);
        }
    }

    private function installPortraits(): void
    {
        $portraits = [
            'robert-chantal' => 'nash.gif', 'elizabeth-deligio' => 'liz.gif', 'brian-derouen' => 'brian.gif', 'meagan-doty' => 'meagan.gif', 'ron-durham' => 'ron.gif', 'tom-maclean' => 'tom.gif', 'elizabeth-nadeau' => 'elizabeth2.gif', 'dan-schwankl' => 'dan.gif', 'aaron-shuman' => 'aaron.gif', 'anika-cunningham' => 'annika.jpg', 'sister-mary-dennis-lentsch' => 'mary.jpg', 'edward-naed-smith' => 'naed.jpg', 'margaret-bryant-gainer' => 'mbgainer.gif', 'don-coleman-soa' => 'doncoleman.gif', 'valerie-fillenwarth' => 'vfillenwarth.gif', 'philip-gates-soa' => 'philgatessm.gif', 'joshua-harris-soa' => 'joshharris.gif', 'melissa-helman' => 'soabiopiccopy.gif', 'martina-leforce' => 'mleforce.gif', 'sheila-salmon' => 'sheilasalmon.gif', 'nathan-slater' => 'nathans.gif', 'mike-vosburg-casey' => 'mvosburg.gif', 'graymon-ward' => 'gwar.gif', 'cathy-webster' => 'cathywebster.gif',
        ];
        foreach ($portraits as $slug => $filename) {
            $prisoner = Prisoner::withUnderReview()->where('slug', $slug)->first();
            $source = base_path('database/data/photos/soaw-austin/'.$filename);
            if (! $prisoner || $prisoner->photo || ! is_file($source)) continue;
            $destination = 'prisoners/'.$slug.'-soaw-austin.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            Storage::disk('public')->put($destination, file_get_contents($source));
            $prisoner->photo = $destination;
            $prisoner->save();
        }
    }

    private function applyCase(PrisonerCase $case, array $update): void
    {
        if (array_key_exists('sentence', $update)) $case->sentence = $update['sentence'];
        if (array_key_exists('days', $update)) $case->documented_imprisoned_for_days = $update['days'];
        if (array_key_exists('months', $update)) $case->imprisoned_for_months = $update['months'];
        if (array_key_exists('days', $update) && $update['days'] !== null) $case->imprisoned_for_months = null;
        if (array_key_exists('months', $update) && $update['months'] !== null) $case->documented_imprisoned_for_days = null;
        foreach (['arrest' => 'arrest_date', 'sentenced' => 'sentenced_date', 'incarceration' => 'incarceration_date', 'release' => 'release_date'] as $key => $field) {
            if (array_key_exists($key, $update)) $case->setPartialDate($field, ...($update[$key] ?? [null]));
        }
        $case->save();
    }

    private function savePreservingSlug(Prisoner $prisoner, array $attributes): void
    {
        $slug = $prisoner->slug;
        $prisoner->fill($attributes);
        $prisoner->save();
        if ($prisoner->slug !== $slug) {
            $prisoner->slug = $slug;
            $prisoner->save();
        }
    }

    private function appendSources(Prisoner $prisoner, array $extra = []): void
    {
        if (! str_contains((string) $prisoner->body, self::SOURCE)) {
            $prisoner->body = trim((string) $prisoner->body).$this->sourceHtml($extra);
            $prisoner->save();
        }
    }

    private function sourceHtml(array $extra = []): string
    {
        $sources = array_merge([['SOAW Austin — Prisoners of Conscience chronology, 1983–2012', self::SOURCE]], $extra);
        $items = array_map(fn ($source) => '<li><a href="'.e($source[1]).'" target="_blank" rel="noopener noreferrer">'.e($source[0]).'</a></li>', $sources);
        return '<h2>Sources</h2><ul>'.implode('', $items).'</ul>';
    }
}
