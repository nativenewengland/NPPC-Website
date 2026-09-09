<?php

namespace App\Filament\Pages;

use App\Models\Prisoner;
use App\Models\PrisonerCase;
use Filament\Pages\Page;

class PrisonerDashboard extends Page {
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Prisoner Database';
    protected static ?string $navigationLabel = 'Dashboard & Stats';
    protected static ?int $navigationSort = -1;
    protected static string $view = 'filament.pages.prisoner-dashboard';

    public function getViewData(): array {
        $prisoners = Prisoner::all();
        $cases = PrisonerCase::all();

        $totalPrisoners = $prisoners->count();
        $inCustody = $prisoners->where('in_custody', true)->count();
        $released = $prisoners->where('released', true)->count();
        $inExile = $prisoners->where('in_exile', true)->count();
        $currentlyInExile = $prisoners->where('currently_in_exile', true)->count();
        $awaitingTrial = $prisoners->where('awaiting_trial', true)->count();
        $imprisonedOrExiled = $prisoners->where('imprisoned_or_exiled', true)->count();

        $accumulatedDaysImprisoned = \App\Support\ImprisonmentDuration::totalDaysAcrossPrisoners($cases);
        $accumulatedDaysInExile = $cases->sum('in_exile_for_days');

        // Gender breakdown
        $genderCounts = $prisoners->groupBy('gender')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        // Race breakdown
        $raceCounts = $prisoners->groupBy('race')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        // Era breakdown
        $eraCounts = $prisoners->groupBy('era')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        // Ideology breakdown (JSON array field)
        $ideologyCounts = [];
        foreach ($prisoners as $prisoner) {
            if ($prisoner->ideologies) {
                foreach ($prisoner->ideologies as $ideology) {
                    $ideologyCounts[$ideology] = ($ideologyCounts[$ideology] ?? 0) + 1;
                }
            }
        }
        arsort($ideologyCounts);

        // Affiliation breakdown (JSON array field)
        $affiliationCounts = [];
        foreach ($prisoners as $prisoner) {
            if ($prisoner->affiliation) {
                foreach ($prisoner->affiliation as $aff) {
                    $affiliationCounts[$aff] = ($affiliationCounts[$aff] ?? 0) + 1;
                }
            }
        }
        arsort($affiliationCounts);

        // State breakdown
        $stateCounts = $prisoners->groupBy('state')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        return [
            'totalPrisoners'            => $totalPrisoners,
            'inCustody'                 => $inCustody,
            'released'                  => $released,
            'inExile'                   => $inExile,
            'currentlyInExile'          => $currentlyInExile,
            'awaitingTrial'             => $awaitingTrial,
            'imprisonedOrExiled'        => $imprisonedOrExiled,
            'accumulatedDaysImprisoned' => $accumulatedDaysImprisoned,
            'accumulatedDaysInExile'    => $accumulatedDaysInExile,
            'genderCounts'              => $genderCounts,
            'raceCounts'                => $raceCounts,
            'eraCounts'                 => $eraCounts,
            'ideologyCounts'            => $ideologyCounts,
            'affiliationCounts'         => $affiliationCounts,
            'stateCounts'               => $stateCounts,
        ];
    }
}
