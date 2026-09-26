<?php

chdir('/var/www/NPPC-Website');
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Api\PrisonerApiController;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\PrisonerSortOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$apply = in_array('--apply', $argv, true);

function followupSources(array $sources): string
{
    return '<h2>Sources</h2><ul>'.implode('', array_map(
        fn ($source) => '<li><a href="'.htmlspecialchars($source[1], ENT_QUOTES).'">'.htmlspecialchars($source[0], ENT_QUOTES).'</a></li>',
        $sources
    )).'</ul>';
}

$people = [
    [
        'name' => 'Bruce Stevens Proctor', 'first_name' => 'Bruce', 'middle_name' => 'Stevens', 'last_name' => 'Proctor',
        'birthdate' => [1943], 'death_date' => [2011], 'gender' => 'Male', 'era' => '1960s',
        'affiliation' => ['Defense Intelligence Agency', 'Air National Guard'], 'ideologies' => ['Anti-war'],
        'description' => 'Bruce Stevens Proctor was a Defense Intelligence Agency employee who examined reconnaissance photographs from Southeast Asia and concluded that the U.S. government was bombing civilian villages while publicly denying it. He resigned from the DIA and joined the Air National Guard to avoid the draft, but his unit was activated for Vietnam. In 1968 he went absent without leave rather than participate in a war he considered immoral and sought refuge in Sweden. Proctor lived there as an American war exile for four years, then immigrated to Canada in 1972. He remained in Winnipeg, worked in education and public service and died there in 2011.',
        'sources' => [
            ['National Archives at Kansas City, “The Sweden File” program', 'https://www.archives.gov/files/kansas-city/press/newsletter/2018-april.pdf'],
            ['Open Books Press, The Sweden File', 'https://openbookspress.com/books/the-sweden-file.php'],
            ['Westphalia Press, The Sweden File', 'https://westphaliapress.org/2015/09/22/the-sweden-file-memoir-of-an-american-expatriate/'],
        ],
        'case' => [
            'charges' => 'Went absent without leave from the Air National Guard after activation for service in Vietnam; no completed prosecution located.',
            'in_exile_since' => [1968], 'end_of_exile' => [2011],
            'sentence' => 'Received refuge in Sweden, moved to Canada in 1972 and remained abroad until his death in 2011.',
        ],
    ],
    [
        'name' => 'Gerry Condon', 'first_name' => 'Gerry', 'last_name' => 'Condon',
        'birthdate' => [1947], 'gender' => 'Male', 'era' => '1960s',
        'affiliation' => ['United States Army', 'American Deserters Committee', 'Veterans For Peace'],
        'ideologies' => ['Anti-war'],
        'description' => 'Gerry Condon was a U.S. Army Special Forces medic trainee who publicly opposed the Vietnam War after hearing returning soldiers describe atrocities against Vietnamese civilians. He refused all military orders and deployment to Vietnam. During his 1969 court-martial at Fort Bragg, Condon escaped and fled through Canada and West Germany to Sweden, where he received humanitarian asylum and worked with the American Deserters Committee. The Army sentenced him in absentia to ten years at hard labor and a dishonorable discharge, later reduced to two years and a bad-conduct discharge. After six years in Canada and Sweden, he returned openly to the United States in 1975 to campaign for unconditional amnesty and was not imprisoned. He later became president of Veterans For Peace.',
        'sources' => [
            ['Veterans For Peace, Gerry Condon’s account', 'https://www.veteransforpeace.org/who-we-are/member-highlights/2018/06/21/vfp-president-gerry-condon-speaks-poor-peoples-campaign-acti'],
            ['Vietnam Veterans Against the War, “War Resister Returns”', 'https://www.vvaw.org/veteran/article/?id=1477'],
            ['Gerry Condon interview on exile and return', 'https://innatenonviolence.org/readings/2011_11.shtml'],
        ],
        'case' => [
            'charges' => 'Refusal of military orders, refusal of deployment to Vietnam and desertion during court-martial proceedings.',
            'in_exile_since' => [1969], 'end_of_exile' => [1975],
            'sentence' => 'Sentenced in absentia to ten years at hard labor and a dishonorable discharge, later reduced to two years and a bad-conduct discharge; returned in 1975 and served no prison time.',
        ],
    ],
    [
        'name' => 'Catherine Marie Kerkow', 'first_name' => 'Catherine', 'middle_name' => 'Marie', 'last_name' => 'Kerkow',
        'aka' => 'Cathy Kerkow; Janice Ann Forte; Odile Ann Pesse; Odile Pesse; Katherine Marie Kerkow',
        'state' => 'Oregon', 'gender' => 'Female', 'race' => 'White', 'era' => '1970s',
        'affiliation' => ['Black Panther Party'], 'ideologies' => ['Anti-war'],
        'description' => 'Catherine Marie “Cathy” Kerkow and Vietnam veteran Willie Roger Holder hijacked Western Airlines Flight 701 on June 3, 1972, demanding $500,000 and the release of Angela Davis. They released the passengers, flew to Algeria and received political asylum. Algeria returned most of the ransom to the United States, and the pair later moved to France. French authorities arrested them on illegal-entry charges in January 1975 and rejected the U.S. extradition request. Kerkow was released and disappeared from public view. The FBI continues to list her on an air-piracy warrant, but her location and present status are unknown; the profile therefore does not count her exile as continuing to the present.',
        'sources' => [
            ['FBI wanted notice for Catherine Marie Kerkow', 'https://www.fbi.gov/wanted/dt/catherine-marie-kerkow/@@download.pdf'],
            ['Le Monde on the French refusal to extradite Holder and Kerkow', 'https://www.lemonde.fr/archives/article/1980/06/16/l-auteur-d-un-detournement-d-avion-est-condamne-a-une-peine-de-prison-avec-sursis_2805787_1819218.html'],
            ['Le Monde history of the American exiles in France', 'https://www.lemonde.fr/m-le-mag/article/2020/10/02/l-exil-paisible-des-black-panthers-de-normandie_6054539_4500055.html'],
        ],
        'case' => [
            'charges' => 'Federal air piracy for the June 3, 1972 hijacking of Western Airlines Flight 701; the warrant remains outstanding.',
            'in_exile_since' => [1972, 6, 3],
            'sentence' => 'Received political asylum in Algeria; France rejected extradition after her 1975 arrest, and she later disappeared from public view.',
        ],
    ],
];

// An existing profile under the shorter name “Catherine Kerkow” was found in
// preflight. Update that record rather than creating this formal-name version.
$people = array_values(array_filter($people, fn ($spec) => $spec['name'] !== 'Catherine Marie Kerkow'));

$names = array_column($people, 'name');
$duplicates = Prisoner::withoutGlobalScopes()
    ->whereIn('name', array_merge($names, ['Bruce Proctor', 'Jerry Condon']))
    ->get(['id', 'name', 'aka']);
if ($duplicates->isNotEmpty()) {
    throw new RuntimeException('Identity guard: possible existing profiles: '.$duplicates->toJson(JSON_UNESCAPED_UNICODE));
}

$holder = Prisoner::withoutGlobalScopes()->where('name', 'Willie Roger Holder')->firstOrFail();
$kerkow = Prisoner::withoutGlobalScopes()->where('name', 'Catherine Kerkow')->with('cases')->firstOrFail();

if (! $apply) {
    echo json_encode(['ready' => true, 'profiles_to_create' => $names, 'profiles_to_update' => [$holder->name, $kerkow->name]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(0);
}

$backup = storage_path('app/backups/before-global-exiles-followup-'.gmdate('Ymd-His').'.sqlite');
DB::unprepared('VACUUM INTO '.DB::connection()->getPdo()->quote($backup));

$result = DB::transaction(function () use ($people, $holder, $kerkow) {
    $created = [];
    foreach ($people as $spec) {
        $caseSpec = $spec['case'];
        $sources = $spec['sources'];
        $birth = $spec['birthdate'] ?? null;
        $death = $spec['death_date'] ?? null;
        unset($spec['case'], $spec['sources'], $spec['birthdate'], $spec['death_date']);

        $profile = new Prisoner();
        $profile->fill(array_merge([
            'in_custody' => false, 'released' => false, 'in_exile' => true,
            'currently_in_exile' => false, 'awaiting_trial' => false,
            'under_review' => false, 'body' => followupSources($sources),
        ], $spec));
        if ($birth) {
            $profile->setPartialDate('birthdate', ...$birth);
        }
        if ($death) {
            $profile->setPartialDate('death_date', ...$death);
        }
        $profile->save();

        $case = new PrisonerCase(['prisoner_id' => $profile->id]);
        $case->charges = $caseSpec['charges'];
        $case->sentence = $caseSpec['sentence'];
        $case->setPartialDate('in_exile_since', ...$caseSpec['in_exile_since']);
        if (! empty($caseSpec['end_of_exile'])) {
            $case->setPartialDate('end_of_exile', ...$caseSpec['end_of_exile']);
        }
        $case->save();

        $placed = PrisonerSortOrder::place($profile, $profile->era);
        $created[] = ['name' => $profile->name, 'slug' => $profile->slug, 'id' => $profile->id, 'case_id' => $case->id, 'sort_order' => $placed['sort_order']];
    }

    $holder->body = followupSources([
        ['Los Angeles Times report on Holder’s 1986 return', 'https://www.latimes.com/archives/la-xpm-1986-07-27-mn-1471-story.html'],
        ['Le Monde on Holder’s French conviction and rejected extradition', 'https://www.lemonde.fr/archives/article/1980/06/16/l-auteur-d-un-detournement-d-avion-est-condamne-a-une-peine-de-prison-avec-sursis_2805787_1819218.html'],
        ['Le Monde history of the American exiles in France', 'https://www.lemonde.fr/m-le-mag/article/2020/10/02/l-exil-paisible-des-black-panthers-de-normandie_6054539_4500055.html'],
    ]);
    $holder->save();

    $kerkow->description = 'Catherine Marie “Cathy” Kerkow and Vietnam veteran Willie Roger Holder hijacked Western Airlines Flight 701 on June 3, 1972, demanding $500,000 and the release of Angela Davis. They released the passengers, flew to Algeria and received political asylum. Algeria returned most of the ransom to the United States, and the pair later moved to France. French authorities arrested them on illegal-entry charges in January 1975 and rejected the U.S. extradition request. Kerkow was released and disappeared from public view. The FBI continues to list her on an air-piracy warrant, but her location and present status are unknown.';
    $kerkow->aka = 'Cathy Kerkow; Janice Ann Forte; Odile Ann Pesse; Odile Pesse; Katherine Marie Kerkow';
    $kerkow->currently_in_exile = false;
    $kerkow->body = followupSources([
        ['FBI wanted notice for Catherine Marie Kerkow', 'https://www.fbi.gov/wanted/dt/catherine-marie-kerkow/@@download.pdf'],
        ['Le Monde on the French refusal to extradite Holder and Kerkow', 'https://www.lemonde.fr/archives/article/1980/06/16/l-auteur-d-un-detournement-d-avion-est-condamne-a-une-peine-de-prison-avec-sursis_2805787_1819218.html'],
        ['Le Monde history of the American exiles in France', 'https://www.lemonde.fr/m-le-mag/article/2020/10/02/l-exil-paisible-des-black-panthers-de-normandie_6054539_4500055.html'],
    ]);
    $kerkow->save();

    // Remove zero-day “exile” spans that were historically auto-derived from
    // releases from ordinary custody. They are not separate exile episodes.
    $derivedCases = PrisonerCase::query()
        ->where(function ($q) use ($holder, $kerkow) {
            $q->where('prisoner_id', $kerkow->id)->where('charges', 'like', '%falsified passport%');
        })
        ->orWhere(function ($q) use ($holder) {
            $q->where('prisoner_id', $holder->id)
                ->where(function ($inner) {
                    $inner->where('charges', 'like', '%falsified passport%')
                        ->orWhere('charges', 'Parole violation');
                });
        })
        ->get();
    foreach ($derivedCases as $derived) {
        $precision = $derived->date_precision ?? [];
        unset($precision['in_exile_since'], $precision['end_of_exile']);
        DB::table('prisoner_cases')->where('id', $derived->id)->update([
            'in_exile_since' => null,
            'end_of_exile' => null,
            'in_exile_for_days' => null,
            'date_precision' => $precision ? json_encode($precision) : null,
            'updated_at' => now(),
        ]);
    }

    return ['created' => $created, 'updated' => [$holder->name, $kerkow->name], 'cleared_derived_exile_spans' => $derivedCases->count()];
});

Cache::forget(PrisonerApiController::cacheKey());
$verify = Prisoner::withoutGlobalScopes()->whereIn('name', $names)->with('cases')->get();
$ok = $verify->count() === count($names)
    && $verify->every(fn ($profile) => $profile->in_exile && ! $profile->currently_in_exile
        && ! $profile->under_review && $profile->cases->contains(fn ($case) => (bool) $case->in_exile_since));
if (! $ok) {
    throw new RuntimeException('Post-publication verification failed');
}

echo json_encode(['backup' => $backup, 'result' => $result, 'verified' => true], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
