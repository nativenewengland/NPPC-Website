<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\ExileDuration;
use App\Support\ImprisonmentDuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PrisonerApiController extends Controller
{
    private const CACHE_KEY = 'api.prisoners.index.v2';

    private const CACHE_TTL = 600; // 10 minutes

    /**
     * Return all prisoners with cases and institutions in the same JSON shape
     * the frontend Vue components expect (matching the old Cloudflare worker output).
     *
     * The full dataset (~700 rows × cases × institutions) is built once and
     * cached for 10 minutes. Pass ?bust=1 to skip the cache on demand. Model
     * observers in App\Providers\AppServiceProvider invalidate the cache on
     * Prisoner / PrisonerCase / Institution save/delete so admin edits show
     * up immediately.
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('bust')) {
            Cache::forget(self::CACHE_KEY);
        }

        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->buildPayload();
        });

        // Public-cacheable for 10 minutes — same TTL as the server-side
        // cache. The data is non-sensitive (public political-prisoner
        // database) so browsers, intermediate proxies, and CDNs can all
        // reuse the same response. Cuts ~1.12 MB off every page load
        // after the first on a given browser. Pass ?bust=1 to force
        // both server and client to revalidate.
        return response()->json($data)->header(
            'Cache-Control',
            'public, max-age=600, stale-while-revalidate=300',
        );
    }

    private function buildPayload(): array
    {
        $data = [];

        // lazy() streams rows in chunks of 1000 so the full Eloquent
        // collection never lives in memory at once. Relationships are
        // eager-loaded per chunk.
        Prisoner::with(['cases.institution'])
            ->orderBy('sort_order')
            ->lazy()
            ->each(function (Prisoner $prisoner) use (&$data) {
                $daysImprisoned = 0;

                // Custody stints are disjoint, so they sum as they are met;
                // exile spans can overlap and are unioned once the whole set
                // is in hand. See ExileDuration.
                $daysInExile = ExileDuration::totalDays($prisoner->cases);

                $cases = $prisoner->cases->map(function ($case) use (&$daysImprisoned) {
                    $daysImprisoned += $case->imprisoned_for_days ?? 0;

                    return [
                        'Indicted' => $case->indicted,
                        'Convicted' => $case->convicted,
                        'Sentenced Date' => $case->partialDateIso('sentenced_date'),
                        'Release Date' => $case->partialDateIso('release_date'),
                        'Charges' => $case->charges ? array_map('trim', explode("\n", $case->charges)) : [],
                        'Prosecutor' => $case->prosecutor,
                        'Judge' => $case->judge,
                        'Plead' => $case->plead,
                        'Sentence' => $case->sentence,
                        'Institution name' => $case->institution ? [$case->institution->name] : [],
                        'Institution city' => $case->institution ? [$case->institution->city] : [],
                        'Institution state' => $case->institution?->state,
                        'Institution security' => $case->institution ? [$case->institution->security] : [],
                        'Arrest Date' => $case->partialDateIso('arrest_date'),
                        'Incarceration Date' => $case->partialDateIso('incarceration_date'),
                        'Mailing address' => $case->institution?->mailing_address,
                        'Physical address' => $case->institution?->physical_address,
                    ];
                })->toArray();

                // Anchor the duration breakdown to the real start date so the
                // calendar diff (below, in calculatePunishment) is accurate.
                $imprisonStart = $prisoner->cases
                    ->map(fn ($c) => $c->incarceration_date ?: $c->arrest_date)
                    ->filter()
                    ->sort()
                    ->first();
                $exileStart = ExileDuration::startFor($prisoner->cases);

                // Non-null only where every case contributing time documents
                // it in months, so the display retains month-level precision.
                $servedMonths = ImprisonmentDuration::documentedMonths($prisoner->cases);

                $data[] = [
                    'id' => $prisoner->id,
                    'slug' => $prisoner->slug,
                    'name' => $prisoner->name,
                    'Photo' => $prisoner->photoUrl(),
                    'Description' => $prisoner->description,
                    'Age' => $prisoner->age,
                    // The card's Birthday field shows a month + day, so only send a
                    // date when we actually know the full day; a year- or month-only
                    // birthdate would render a defaulted "1st" that isn't real.
                    'Birthdate' => $prisoner->datePrecisionFor('birthdate') === 'day'
                        ? $prisoner->birthdate?->format('Y-m-d')
                        : null,
                    'Death date' => $prisoner->partialDateIso('death_date'),
                    'Gender' => $prisoner->gender,
                    'Race' => $prisoner->race,
                    'AKA' => $prisoner->aka,
                    'inmateNumber' => $prisoner->inmate_number,
                    'State' => $prisoner->state,
                    'Address' => $prisoner->address,
                    'latitude' => $prisoner->lat ? (float) $prisoner->lat : null,
                    'longitude' => $prisoner->lng ? (float) $prisoner->lng : null,
                    'Era' => $prisoner->era,
                    'Ideologies' => $prisoner->ideologies ?? [],
                    'Affiliation' => ! empty($prisoner->affiliation) ? $prisoner->affiliation : null,
                    'In Custody' => $prisoner->in_custody,
                    'Released' => $prisoner->released,
                    'In Exile' => $prisoner->currently_in_exile,
                    'Currently in Exile' => $prisoner->currently_in_exile,
                    'Imprisoned or Exiled' => $prisoner->imprisoned_or_exiled ? 'T' : null,
                    'Awaiting Trial' => $prisoner->awaiting_trial,
                    'Website' => $prisoner->website,
                    'Twitter' => $prisoner->twitter,
                    'Facebook' => $prisoner->facebook,
                    'Instagram' => $prisoner->instagram,
                    'Years Spent In Prison' => array_map('strval', $prisoner->getIncarcerationYearsArray()),
                    'SortOrder' => $prisoner->sort_order,
                    'cases' => $cases,
                    'imprisonedFor' => $daysImprisoned,
                    // Non-null only where a source documented the time served
                    // in months; consumers should format years/months rather than
                    // deriving a day-level span from imprisonedFor.
                    'imprisonedForMonths' => $servedMonths,
                    'inExileFor' => $daysInExile,
                    'calculatedPunishment' => $this->calculatePunishment(
                        $daysImprisoned,
                        $daysInExile,
                        $imprisonStart,
                        $exileStart,
                        $servedMonths,
                    ),
                    'Minor Case' => (bool) $prisoner->minor_case,
                    // Convenience boolean aliases used by Vue filter system
                    'inCustody' => $prisoner->in_custody,
                    'released' => $prisoner->released,
                    'inExile' => $prisoner->currently_in_exile,
                    'awaitingTrial' => $prisoner->awaiting_trial,
                    'imprisonedOrExiled' => $prisoner->imprisoned_or_exiled,
                ];
            });

        return $data;
    }

    private function calculatePunishment(int $daysImprisoned, int $daysInExile, $imprisonStart = null, $exileStart = null, ?int $servedMonths = null): string
    {
        $result = '';

        if ($daysImprisoned > 0) {
            if ($servedMonths > 0) {
                // Share the profile formatter without inventing day precision.
                $result .= 'Imprisoned For '.strtolower(ImprisonmentDuration::phrase($imprisonStart, $daysImprisoned, $servedMonths));
            } else {
                ['years' => $years, 'months' => $months, 'days' => $days] = ImprisonmentDuration::breakdown($imprisonStart, $daysImprisoned);
                $result .= "Imprisoned For {$years} years {$months} months {$days} days";
            }
        }

        if ($daysInExile > 0) {
            if ($result) {
                $result .= '<br/>';
            }
            ['years' => $years, 'months' => $months, 'days' => $days] = ImprisonmentDuration::breakdown($exileStart, $daysInExile);
            $result .= "In Exile For {$years} years {$months} months {$days} days";
        }

        return $result;
    }

    /**
     * Cache key exposed so model observers can invalidate it.
     */
    public static function cacheKey(): string
    {
        return self::CACHE_KEY;
    }
}
