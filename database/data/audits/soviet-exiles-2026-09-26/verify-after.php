<?php
chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Prisoner;
use App\Models\PrisonerCase;

$newNames = [
    'Craig W. Anderson', 'John Barilla', 'Richard D. Bailey', 'Michael A. Lindner',
    'Maurice Hyman Halperin', 'Wade E. Roberts', 'Morris Cohen', 'Lona Cohen',
    'Joel Barr', 'Alfred Sarant', 'Edward Lee Howard', 'Glenn Michael Souther',
    'William Hamilton Martin', 'Bernon F. Mitchell',
];
$iwwNames = ['Bill Haywood', 'George Andreytchine', 'Vladimir Lossieff', 'J. H. Beyer', 'Herbert McCutcheon', 'Grover H. Perry', 'Charles Rothfiser', 'Leo Laukki', 'Fred Jaakkola'];
$gastoniaNames = ['Fred Beal', 'Clarence Miller', 'George Carter', 'Joseph Harrison', 'W. M. McGinnis', 'Louis McLaughlin', 'K. Y. Hendricks'];

$new = Prisoner::withoutGlobalScopes()->whereIn('name', $newNames)->with('cases')->get();
$checks = [
    'new_profile_count' => $new->count() === 14,
    'one_case_each' => $new->every(fn ($p) => $p->cases->count() === 1),
    'all_have_exile_start' => $new->every(fn ($p) => $p->cases->first()?->in_exile_since !== null),
    'all_public' => $new->every(fn ($p) => ! $p->under_review),
    'all_historical_exile' => $new->every(fn ($p) => $p->in_exile && ! $p->currently_in_exile),
    'all_have_sources' => $new->every(fn ($p) => str_contains($p->body ?? '', '<h2>Sources</h2>')),
    'iww_all_historical_exile' => Prisoner::withoutGlobalScopes()->whereIn('name', $iwwNames)->get()->every(fn ($p) => $p->in_exile && ! $p->currently_in_exile),
    'iww_cases_dated' => PrisonerCase::query()->whereIn('prisoner_id', Prisoner::withoutGlobalScopes()->whereIn('name', array_slice($iwwNames, 1))->pluck('id'))->whereYear('in_exile_since', 1921)->count() === 8,
    'haywood_false_exile_removed' => PrisonerCase::query()->where('id', 'ab353c59-49d8-4c94-9cde-0f8c5b25f896')->whereNull('in_exile_since')->exists(),
    'gastonia_soviet_five' => Prisoner::withoutGlobalScopes()->whereIn('name', ['Fred Beal', 'Clarence Miller', 'Joseph Harrison', 'Louis McLaughlin', 'K. Y. Hendricks'])->where('in_exile', true)->count() === 5,
    'gastonia_two_not_exiled' => Prisoner::withoutGlobalScopes()->whereIn('name', ['George Carter', 'W. M. McGinnis'])->where('in_exile', false)->count() === 2,
    'dennis_1931_exile' => PrisonerCase::query()->where('prisoner_id', 'e88088dd-3996-4b81-9bb2-3162e0420b30')->whereYear('in_exile_since', 1931)->exists(),
    'lockshin_enriched' => PrisonerCase::query()->where('prisoner_id', '982032dc-f66a-4dfc-9cd6-b429f2523002')->whereDate('in_exile_since', '1986-10-08')->exists(),
];

echo json_encode([
    'ok' => ! in_array(false, $checks, true),
    'checks' => $checks,
    'new_profiles' => $new->map(fn ($p) => [
        'name' => $p->name,
        'slug' => $p->slug,
        'exile_start' => $p->cases->first()?->in_exile_since?->format('Y-m-d'),
        'exile_end' => $p->cases->first()?->end_of_exile?->format('Y-m-d'),
        'date_precision' => $p->cases->first()?->date_precision,
    ])->values(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

if (in_array(false, $checks, true)) exit(1);
