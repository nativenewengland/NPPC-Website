<?php

namespace App\Models;

use App\Models\Concerns\HasPartialDates;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $prisoner_id
 * @property string|null $institution_id
 * @property string|null $charges
 * @property string|null $arrest_date
 * @property string|null $indicted
 * @property string|null $convicted
 * @property string|null $plead
 * @property string|null $sentenced_date
 * @property string|null $incarceration_date
 * @property string|null $release_date
 * @property string|null $death_in_custody_date
 * @property string|null $in_exile_since
 * @property string|null $end_of_exile
 * @property string|null $prosecutor
 * @property string|null $judge
 * @property string|null $sentence
 * @property int|null $imprisoned_for_days
 * @property int|null $imprisoned_for_months
 * @property int|null $in_exile_for_days
 */
final class PrisonerCase extends Model
{
    use HasPartialDates;

    protected $table = 'prisoner_cases';

    protected $casts = [
        'arrest_date' => 'date',
        'sentenced_date' => 'date',
        'incarceration_date' => 'date',
        'release_date' => 'date',
        'death_in_custody_date' => 'date',
        'in_exile_since' => 'date',
        'end_of_exile' => 'date',
        'date_precision' => 'array',
    ];

    public function partialDateFields(): array
    {
        return [
            'arrest_date', 'sentenced_date', 'incarceration_date', 'release_date',
            'death_in_custody_date', 'in_exile_since', 'end_of_exile',
        ];
    }

    public static function booted(): void
    {
        parent::booted();

        self::saving(function (self $case) {
            // A death in custody ends the incarceration on that date, so the
            // release date is set to the same day (this also makes
            // imprisoned_for_days, computed below, stop at death).
            if ($case->death_in_custody_date) {
                $case->release_date = $case->death_in_custody_date;
                $case->mirrorDatePrecision('death_in_custody_date', 'release_date');
            }

            // Auto-derive in_exile_since from release_date when the prisoner
            // is flagged as exiled but the case row has no in_exile_since
            // explicitly set. The common path: a defendant is held in
            // immigration custody, then released *into* exile (deported
            // or self-deported); release_date and in_exile_since are the
            // same day. For prisoners with a documented gap between
            // release and exile (e.g. bail-jumpers like Bill Haywood),
            // in_exile_since should be set explicitly and this branch
            // does nothing because the field is non-null.
            if (! $case->in_exile_since && $case->release_date) {
                $prisoner = $case->prisoner;
                if ($prisoner && ($prisoner->in_exile || $prisoner->currently_in_exile)
                    && ! $case->custodyResumedAfterRelease()) {
                    $case->in_exile_since = $case->release_date;
                    $case->mirrorDatePrecision('release_date', 'in_exile_since');
                }
            }

            $case->imprisoned_for_days = $case->computeImprisonedForDays();
            $case->in_exile_for_days = $case->computeInExileForDays();
        });
    }

    /**
     * Was the prisoner back in custody after this case's release date?
     *
     * Guards the auto-derive above. A release that is followed by another
     * incarceration did not put anybody into exile — they were out, then they
     * were inside again — and the row that most often trips this is not even a
     * release: an escape is recorded as a release_date, because there is no
     * other column for the day custody ended. William Morales's Bellevue
     * escape (May 21, 1979) was auto-derived into "in exile since 1979",
     * alongside the real exile that began when Mexico flew him to Cuba in
     * 1988, and the two spans were then added together on his public profile.
     *
     * Only a guard on the derived value: an in_exile_since set explicitly by
     * a curator or a data script never reaches this, since the branch above
     * runs only when the field is empty.
     *
     * Note the ordering caveat — a case row saved before its later sibling
     * exists cannot see custody that has not been entered yet, so this stops
     * a bad value being re-derived (an admin edit, a recompute run) rather
     * than guaranteeing one was never written. Existing rows are corrected by
     * data script.
     */
    public function custodyResumedAfterRelease(): bool
    {
        if (! $this->release_date || ! $this->prisoner_id) {
            return false;
        }

        return self::query()
            ->where('prisoner_id', $this->prisoner_id)
            ->when($this->id, fn ($q) => $q->where('id', '!=', $this->id))
            ->where(function ($q) {
                $q->where('incarceration_date', '>', $this->release_date)
                    ->orWhere('arrest_date', '>', $this->release_date);
            })
            ->exists();
    }

    /**
     * Days in custody for this case, or null when they cannot be known.
     *
     * Both duration columns are stored, and this hook is the only thing that
     * writes them — which means they are recomputed only when the *case* row
     * is saved. Changing a flag on the prisoner (in_custody, awaiting_trial)
     * changes what these should be without touching the case, so the stored
     * value silently goes stale. prisoners:recompute-imprisonment re-runs this
     * across the table; it calls the same method the hook does so the two can
     * never drift apart.
     */
    public function computeImprisonedForDays(): ?int
    {
        // A documented duration outranks the date arithmetic. Where a source
        // states the time served in months and the endpoints are uncertain —
        // Bill Sutherland's 38 months against summaries that variously give
        // 1942-45, 1943-45 and 1943-46 — the months are the fact and the dates
        // are the guess, so the stored day count is walked forward from the
        // start rather than measured between two dates that disagree.
        if ($this->imprisoned_for_months > 0) {
            $anchor = $this->incarceration_date ?? $this->arrest_date;

            if ($anchor) {
                $start = Carbon::parse($anchor)->startOfDay();

                return (int) $start->diffInDays($start->copy()->addMonths((int) $this->imprisoned_for_months));
            }

            // Nothing to anchor to. The average Gregorian month keeps the
            // aggregates honest; the public counter renders the months
            // themselves and never sees this figure.
            return (int) round($this->imprisoned_for_months * 30.436875);
        }

        if (! $this->incarceration_date) {
            return null;
        }

        // Partial dates use placeholder days for storage. Those placeholders
        // cannot establish an exact duration; a documented term above can.
        if ($this->datePrecisionFor('incarceration_date') !== 'day'
            || ($this->release_date && $this->datePrecisionFor('release_date') !== 'day')) {
            return null;
        }

        if ($this->release_date) {
            $start = Carbon::parse($this->incarceration_date);
            $end = Carbon::parse($this->release_date);

            // A release BEFORE the custody starts is not a short sentence, it
            // is a mismatched pair: one row holding an arrest from one episode
            // and a release from an earlier, unrelated one. Lifelong
            // activists collect several arrests, and a single case row cannot
            // hold two of them.
            //
            // diffInDays() is absolute, so such a pair yields a large,
            // entirely believable figure — Paul Magno's 2013 arrest against a
            // 1986 release rendered as "Imprisoned For 27 years 10 months 10
            // days" on his public profile. Suppress it instead, for the same
            // reason the age hook in Prisoner::saving() suppresses an
            // impossible age rather than publishing the absolute difference:
            // no counter is better than a fabricated one.
            if ($end->lt($start)) {
                return null;
            }

            return (int) $start->diffInDays($end);
        }

        // No release date recorded. Only count up to today when the prisoner
        // is actually still detained (in custody or awaiting trial); a
        // released prisoner whose release date was never recorded has an
        // unknown end, so leave it null rather than inflating "time served"
        // all the way to the present. Mirrors the stats-chart logic in
        // Prisoner::getIncarcerationYearsArray().
        $prisoner = $this->prisoner;
        $stillDetained = $prisoner && ($prisoner->in_custody || $prisoner->awaiting_trial);

        return $stillDetained
            ? (int) Carbon::parse($this->incarceration_date)->diffInDays(Carbon::today())
            : null;
    }

    /** Days in exile for this case, or null when they cannot be known. */
    public function computeInExileForDays(): ?int
    {
        if (! $this->in_exile_since) {
            return null;
        }

        if ($this->end_of_exile) {
            $start = Carbon::parse($this->in_exile_since);
            $end = Carbon::parse($this->end_of_exile);

            // Same guard as computeImprisonedForDays(): an exile that ends
            // before it begins is a mismatched pair, not a duration.
            if ($end->lt($start)) {
                return null;
            }

            return (int) $start->diffInDays($end);
        }

        // No end-of-exile recorded. Only count up to today when the prisoner
        // is actually still in exile; a historical exile with an unknown end
        // (return or death abroad never documented) stays null rather than
        // counting to the present. Mirrors the guard above.
        $prisoner = $this->prisoner;
        $stillExiled = $prisoner && $prisoner->currently_in_exile;

        return $stillExiled
            ? (int) Carbon::parse($this->in_exile_since)->diffInDays(Carbon::today())
            : null;
    }

    public function prisoner(): BelongsTo
    {
        return $this->belongsTo(Prisoner::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
