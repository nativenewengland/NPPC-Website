<?php

namespace App\Http\Controllers;

use App\Models\AnnualReport;
use App\Models\ArchiveRecord;
use App\Models\Article;
use App\Models\Author;
use App\Models\CalendarEntry;
use App\Models\Event;
use App\Models\Faq;
use App\Models\HistoryEra;
use App\Models\Institution;
use App\Models\Page;
use App\Models\Prisoner;
use App\Models\PrisonerCase;
use App\Support\AccentInsensitiveSearch;
use App\Support\ExileDuration;
use App\Support\IncarcerationCostRates;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\QuizResult;
use App\Models\Staff;
use App\Models\Timeline;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class SiteController extends Controller {
    public function page(string $slug) {
        // Custom Blade views take priority over DB pages so that
        // admin-created pages (used for nav placement) don't override
        // hand-crafted designs like careers-internships, about, etc.
        if (view()->exists('pages.'.$slug)) {
            return view('pages.'.$slug);
        }

        if ($page = Page::getBySlug($slug)) {
            return view('page', compact('page'));
        }

        abort(404);
    }

    /**
     * The database page, arrived at through a filter deep link such as
     * /database/era/1980s or /database/ideology/anarchism.
     *
     * The same view as /database — the filtering itself belongs to the Vue
     * app, so all this does is hand it the facet to preselect and give the
     * page a title that says what you are looking at, which is what makes
     * these URLs worth sharing. The value is matched against the real filter
     * options client-side; an unknown one simply leaves the page unfiltered
     * rather than 404ing, because a stale link to a renamed ideology should
     * still show the database.
     */
    public function databaseFacet(string $path) {
        $segments = explode('/', trim($path, '/'));
        $facets = [];

        // Pairs: key, value, key, value. The route pattern has already
        // guaranteed the shape and the key names, so this only has to split.
        for ($i = 0; $i + 1 < count($segments); $i += 2) {
            $facets[$segments[$i]] = array_values(array_filter(explode(',', $segments[$i + 1])));
        }

        return view('pages.database', ['facets' => $facets]);
    }

    public function timeline() {
        return view('pages.timeline', ['timelines' => Timeline::query()->orderBy('year')->get()]);
    }

    public function archiveRecords(Request $request) {
        $q = trim((string) $request->query('q', ''));
        $collection = $request->query('collection');
        $recordType = $request->query('record_type');
        $sourceFormat = $request->query('source_format');
        $year = $request->query('year');
        $subject = $request->query('subject');
        $sort = $request->query('sort', 'relevance');
        $includeNonDigitized = filter_var($request->query('include_nondigitized'), FILTER_VALIDATE_BOOLEAN);

        $base = ArchiveRecord::published();
        if (! $includeNonDigitized) {
            $base->digitized();
        }

        $facetQuery = (clone $base);
        if ($q !== '') {
            $facetQuery->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('authors', 'like', "%{$q}%")
                    ->orWhere('publisher', 'like', "%{$q}%");
            });
        }

        $countBy = function (string $column) use ($facetQuery) {
            return (clone $facetQuery)
                ->whereNotNull($column)
                ->selectRaw("{$column} as label, COUNT(*) as count")
                ->groupBy($column)
                ->orderByDesc('count')
                ->limit(200)
                ->get()
                ->map(fn ($r) => ['label' => (string) $r->label, 'count' => (int) $r->count])
                ->all();
        };

        // Year facet shows every year sorted DESC by year (not top-N
        // by count) so historical years (1886 Haymarket, 1918 Debs,
        // 1927 Sacco-Vanzetti) aren't knocked out by densely-
        // populated 2010s/2020s buckets.
        $yearFacet = (clone $facetQuery)
            ->whereNotNull('year')
            ->selectRaw('year as label, COUNT(*) as count')
            ->groupBy('year')
            ->orderByDesc('year')
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->label, 'count' => (int) $r->count])
            ->all();

        $collectionFacet = $countBy('collection');

        // Collapse "Anarchist Black Cross — X" sub-collections into a
        // single parent ABC entry whose count is the sum of all
        // chapters. The children are kept on the parent so the
        // template can render them indented.
        $abcChildren = [];
        $abcCount = 0;
        $collectionFacetFiltered = [];
        foreach ($collectionFacet as $f) {
            if ($f['label'] === 'Anarchist Black Cross' || str_starts_with($f['label'], 'Anarchist Black Cross —') || str_starts_with($f['label'], 'Anarchist Black Cross -')) {
                $abcCount += $f['count'];
                if ($f['label'] !== 'Anarchist Black Cross') {
                    $abcChildren[] = $f;
                }
            } else {
                $collectionFacetFiltered[] = $f;
            }
        }
        if ($abcCount > 0) {
            usort($abcChildren, fn ($a, $b) => $b['count'] <=> $a['count']);
            array_unshift($collectionFacetFiltered, [
                'label' => 'Anarchist Black Cross',
                'count' => $abcCount,
                'children' => $abcChildren,
            ]);
        }

        $facets = [
            'collection' => $collectionFacetFiltered,
            'record_type' => $countBy('record_type'),
            'source_format' => $countBy('source_format'),
            'year' => $yearFacet,
        ];

        $subjectCounts = [];
        foreach ((clone $facetQuery)->whereNotNull('subjects')->pluck('subjects') as $list) {
            foreach ((array) $list as $s) {
                $s = trim((string) $s);
                if ($s === '') {
                    continue;
                }
                $subjectCounts[$s] = ($subjectCounts[$s] ?? 0) + 1;
            }
        }
        arsort($subjectCounts);
        $facets['subject'] = array_map(
            fn ($label, $count) => ['label' => (string) $label, 'count' => (int) $count],
            array_keys($subjectCounts),
            array_values($subjectCounts)
        );
        $facets['subject'] = array_slice($facets['subject'], 0, 200);

        $query = (clone $base);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('authors', 'like', "%{$q}%")
                    ->orWhere('publisher', 'like', "%{$q}%");
            });
        }
        if ($collection) {
            // Filtering by the synthetic ABC parent matches every
            // chapter sub-collection.
            if ($collection === 'Anarchist Black Cross') {
                $query->where(function ($w) {
                    $w->where('collection', 'Anarchist Black Cross')
                        ->orWhere('collection', 'like', 'Anarchist Black Cross —%')
                        ->orWhere('collection', 'like', 'Anarchist Black Cross -%');
                });
            } else {
                $query->where('collection', $collection);
            }
        }
        if ($recordType) {
            $query->where('record_type', $recordType);
        }
        if ($sourceFormat) {
            $query->where('source_format', $sourceFormat);
        }
        if ($year) {
            $query->where('year', (int) $year);
        }
        if ($subject) {
            $query->where('subjects', 'like', '%"'.$subject.'"%');
        }

        match ($sort) {
            'newest' => $query->orderByDesc('date')->orderByDesc('year'),
            'oldest' => $query->orderBy('date')->orderBy('year'),
            'title' => $query->orderBy('title'),
            default => $query->orderBy('sort_order')->orderBy('title'),
        };

        $records = $query->paginate(25)->withQueryString();
        $total = $records->total();

        return view('pages.archive', compact(
            'records',
            'facets',
            'total',
            'q',
            'collection',
            'recordType',
            'sourceFormat',
            'year',
            'subject',
            'sort',
            'includeNonDigitized'
        ));
    }

    public function history() {
        return view('pages.history', ['eras' => HistoryEra::with('topics')->orderBy('sort_order')->get()]);
    }

    public function topics(Request $request, ?string $slug = null) {
        $rootTopics = Topic::published()->roots()
            ->with('children')->orderBy('sort_order')->get();

        $activeTopic = null;      // root section (column 1)
        $activeChild = null;      // depth-1 sub-topic (column 2)
        $activeGrandchild = null; // depth-2 nested topic (column 3)
        $showIndex = ($slug === 'index');
        $showContribute = ($slug === 'contributions');
        $indexGroups = collect();

        if ($showIndex) {
            // Alphabetical index of every sub-topic (leaf), grouped by first
            // letter. A leading article ("The ...") is ignored for sorting.
            $indexGroups = Topic::published()
                ->whereNotNull('parent_id')
                ->get()
                ->sortBy(fn ($t) => $this->indexSortKey($t->title), SORT_NATURAL | SORT_FLAG_CASE)
                ->groupBy(fn ($t) => strtoupper(mb_substr($this->indexSortKey($t->title), 0, 1)));
        } elseif ($slug && ! $showContribute) {
            // Resolve the requested topic to its section / sub-topic / nested
            // ancestry so the explorer can surface each level in its own column
            // (roots → sub-topics → nested topics). Supports up to two levels of
            // nesting; e.g. Everett Massacre under Industrial Workers of the World.
            $found = Topic::published()->where('slug', $slug)->first();

            if ($found && $found->parent_id === null) {
                $activeTopic = $found;
            } elseif ($found) {
                $parent = $found->parent;
                if ($parent && $parent->parent_id === null) {
                    // Depth-1 sub-topic — surface it within its section.
                    $activeTopic = $parent;
                    $activeChild = $found;
                } else {
                    // Depth-2 nested topic — surface its section, sub-topic, and self.
                    $activeChild = $parent;
                    $activeTopic = $parent ? $parent->parent : null;
                    $activeGrandchild = $found;
                }
            }
        }

        if (! $showIndex && ! $showContribute && ! $activeTopic && $rootTopics->isNotEmpty()) {
            $activeTopic = $rootTopics->first();
        }

        // Related prisoners — only for sub-topics (any topic with a parent:
        // content pages, and now nested topics like Everett Massacre under IWW).
        // The root section pages and the Introduction are overviews, so they
        // show their essay rather than a case list.
        $relatedPrisoners = collect();
        $displayTopic = $activeGrandchild ?: ($activeChild ?: $activeTopic);
        if ($displayTopic && $displayTopic->parent_id) {
            $searchTerms = [strtolower($displayTopic->title)];

            $relatedPrisoners = Prisoner::where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('ideologies', 'like', "%{$term}%")
                      ->orWhere('affiliation', 'like', "%{$term}%")
                      ->orWhere('era', 'like', "%{$term}%")
                      ->orWhere('description', 'like', "%{$term}%");
                }
            })->limit(20)->get();
        }

        // Compact search index of every published topic (title, slug, ancestor
        // path) for the explorer's search box — searched client-side, like the
        // ecfr.eu mapping explorer, so results appear as you type.
        $all = Topic::published()->get(['id', 'title', 'slug', 'parent_id']);
        $byId = $all->keyBy('id');
        $searchIndex = $all->map(function ($t) use ($byId) {
            $path = [];
            $p = $t->parent_id ? $byId->get($t->parent_id) : null;
            while ($p) {
                array_unshift($path, $p->title);
                $p = $p->parent_id ? $byId->get($p->parent_id) : null;
            }

            return ['t' => $t->title, 's' => $t->slug, 'p' => implode(' → ', $path)];
        })->values();

        return view('pages.topics', compact('rootTopics', 'activeTopic', 'activeChild', 'activeGrandchild', 'relatedPrisoners', 'showIndex', 'showContribute', 'indexGroups', 'searchIndex'));
    }

    /** Sort/group key for the topic index: drops a leading article. */
    private function indexSortKey(string $title): string {
        return ltrim(preg_replace('/^(the|a|an)\s+/i', '', trim($title)));
    }

    public function memorial() {
        // A memorial starfield — one star for every political prisoner in the
        // database (after gazaschildren.com's "one star for every name"). Only
        // the few fields the starfield needs are sent, keyed short to keep the
        // embedded payload small. Each carries a year (`y`) — the earliest of
        // its cases' arrest/incarceration/sentencing dates, or the era's year —
        // so the timeline player can ignite stars in chronological order.
        $people = Prisoner::with(['cases:id,prisoner_id,arrest_date,incarceration_date,sentenced_date'])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'era', 'death_date', 'in_custody'])
            ->map(function ($p) {
                $year = null;
                foreach ($p->cases as $c) {
                    foreach (['incarceration_date', 'arrest_date', 'sentenced_date'] as $f) {
                        if ($c->$f) {
                            $y = (int) \Carbon\Carbon::parse($c->$f)->year;
                            if ($y > 1000) {
                                $year = $year ? min($year, $y) : $y;
                            }
                        }
                    }
                }
                if (! $year && $p->era && preg_match('/\d{4}/', $p->era, $m)) {
                    $year = (int) $m[0];
                }

                return [
                    'n' => $p->name,
                    'u' => $p->url,
                    'e' => $p->era,
                    'c' => (bool) $p->in_custody,   // still imprisoned
                    'd' => (bool) $p->death_date,    // deceased
                    'y' => $year,                    // year of imprisonment (nullable)
                ];
            })
            ->values();

        $years = $people->pluck('y')->filter()->values();
        $minYear = $years->isNotEmpty() ? (int) $years->min() : 1850;
        $maxYear = (int) date('Y');

        // Give anyone with no datable year the earliest year, so they still
        // appear (lit from the start) rather than never igniting.
        $people = $people->map(function ($r) use ($minYear) {
            $r['y'] = $r['y'] ?: $minYear;

            return $r;
        })->values();

        return view('pages.memorial', [
            'people' => $people,
            'count' => $people->count(),
            'minYear' => $minYear,
            'maxYear' => $maxYear,
        ]);
    }

    public function birthdays(Request $request) {
        // Currently-incarcerated prisoners with a known birthdate, for
        // the letter-writing birthday calendar.
        $prisoners = Prisoner::whereNotNull('birthdate')
            ->whereNull('death_date')
            ->where('in_custody', true)
            ->orderBy('birthdate')
            ->get();

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[$m] = [];
        }
        foreach ($prisoners as $p) {
            try {
                $d = \Carbon\Carbon::parse($p->birthdate);
            } catch (\Throwable $e) {
                continue;
            }
            $byMonth[(int) $d->month][] = ['prisoner' => $p, 'day' => (int) $d->day];
        }
        foreach ($byMonth as $m => &$entries) {
            usort($entries, fn ($a, $b) => $a['day'] <=> $b['day']);
        }
        unset($entries);

        $month = (int) ($request->input('month', date('n')));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        return view('pages.birthdays', [
            'byMonth' => $byMonth,
            'month' => $month,
            'todayMonth' => (int) date('n'),
            'todayDay' => (int) date('j'),
            'totalCount' => $prisoners->count(),
        ]);
    }

    public function calendar(Request $request) {
        $month = (int) ($request->input('month', date('n')));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $day = $request->input('day');
        $view = $request->input('view', 'month');

        $entries = CalendarEntry::with('prisoner')
            ->where('month', $month)
            ->where('published', true)
            ->orderBy('day')
            ->orderBy('year')
            ->get();

        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $today = (int) date('j');
        $currentMonth = (int) date('n');

        // Day view: collect ALL entries for the selected day (a single
        // date can host multiple historically-significant events; e.g.
        // May 4 = both Kent State 1970 and the Original 13 Freedom
        // Riders 1961).
        $dayEntries = collect();
        $selectedDay = null;
        if ($view === 'day' && $day) {
            $selectedDay = (int) $day;
            $dayEntries = $entries->where('day', $selectedDay)->values();
        }
        if ($view === 'day' && $dayEntries->isEmpty() && $month === $currentMonth) {
            $selectedDay = $today;
            $dayEntries = $entries->where('day', $today)->values();
        }
        if ($view === 'day' && $dayEntries->isEmpty() && $entries->isNotEmpty()) {
            $selectedDay = (int) $entries->first()->day;
            $dayEntries = $entries->where('day', $selectedDay)->values();
        }

        // Back-compat: views that still reference $dayEntry get the first.
        $dayEntry = $dayEntries->first();

        return view('pages.calendar', compact('entries', 'month', 'monthName', 'today', 'currentMonth', 'view', 'dayEntry', 'dayEntries', 'selectedDay'));
    }

    public function store(Request $request) {
        $category = $request->input('category');
        // Books and Zines always sort to the very back of the grid (after every
        // other category — books, then zines), then by sort_order within each group.
        $products = Product::published()
            ->orderByRaw("CASE WHEN category = 'Books' THEN 1 WHEN category = 'Zines' THEN 2 ELSE 0 END")
            ->orderBy('sort_order')
            ->get();
        $categories = Product::published()->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');
        $featured = Product::published()->featured()->first();

        return view('pages.store', compact('products', 'categories', 'featured', 'category'));
    }

    public function storeProduct(string $slug) {
        $product = Product::published()->where('slug', $slug)->firstOrFail();

        $related = Product::published()
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        $categories = Product::published()->whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category');

        return view('pages.store-product', compact('product', 'related', 'categories'));
    }

    public function events(Request $request) {
        $tab = $request->input('tab', 'upcoming');
        $series = Event::published()->whereNotNull('series')->where('series', '!=', '')->distinct()->pluck('series');

        // Optional filter: /events?series=... narrows both lists to one series.
        $activeSeries = $request->input('series');
        if ($activeSeries !== null && ! $series->contains($activeSeries)) {
            $activeSeries = null;
        }

        $upcoming = Event::published()->upcoming()
            ->when($activeSeries, fn ($q) => $q->where('series', $activeSeries))
            ->get();
        $past = Event::published()->past()
            ->when($activeSeries, fn ($q) => $q->where('series', $activeSeries))
            ->get();

        return view('pages.events', compact('upcoming', 'past', 'series', 'tab', 'activeSeries'));
    }

    public function volunteer() {
        return view('pages.volunteer');
    }

    public function chapters() {
        return view('pages.chapters');
    }

    public function prisonerOutreach() {
        $prisoners = Prisoner::where('in_custody', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'name', 'slug', 'last_name', 'first_name']);

        // "Meet Political Prisoners" carousel: a fresh random set on every
        // page load — currently imprisoned people only, with a photo and bio.
        $meetPrisoners = Prisoner::where('in_custody', true)
            ->whereNotNull('photo')
            ->where('photo', '!=', '')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->inRandomOrder()
            ->limit(30)
            ->get(['id', 'name', 'slug', 'photo', 'state', 'description',
                'in_custody', 'released', 'awaiting_trial', 'in_exile']);

        return view('pages.prisoner-outreach', compact('prisoners', 'meetPrisoners'));
    }

    public function staff(Request $request) {
        $group = $request->input('group');
        if ($group === 'board') {
            $staff = Staff::getBoardMembers();
        } elseif ($group === 'staff') {
            $staff = Staff::getStaffMembers();
        } else {
            $staff = Staff::where('published', true)->get();
        }

        return view('pages.staff', ['staff' => $staff]);
    }

    public function boardOfDirectors() {
        return view('pages.board-of-directors', ['directors' => Staff::getBoardMembers()]);
    }

    public function partners() {
        $partners = \App\Models\Partner::published()->orderBy('sort_order')->get();

        return view('pages.partners', compact('partners'));
    }

    public function about() {
        return view('pages.about');
    }

    public function signUp(Request $request) {
        $request->validate(['email' => 'required|email']);
        \App\Models\EmailSubscriber::firstOrCreate(['email' => $request->input('email')]);

        return redirect()->back()->with('subscribed', true);
    }

    public function annualReport() {
        return view('pages.annual_reports', ['reports' => AnnualReport::all()]);
    }

    public function map() {
        return view('pages.map');
    }

    public function nppcQuiz() {
        return view('pages.nppc-quiz');
    }

    public function nppcQuizResult(Request $request) {
        $data = $request->validate([
            'profile'              => ['required', 'string', 'max:80'],
            'values_scores'        => ['required', 'array'],
            'values_scores.*'      => ['integer', 'between:0,100'],
            'engagement_score'     => ['required', 'integer', 'between:0,30'],
            'engagement_tier'      => ['required', 'string', 'max:40'],
            'perception_avg_error' => ['nullable', 'integer', 'between:0,100'],
            'knowledge_correct'    => ['required', 'integer', 'between:0,50'],
            'knowledge_total'      => ['required', 'integer', 'between:1,50'],
            'knowledge_pct'        => ['required', 'integer', 'between:0,100'],
            'knowledge_tier'       => ['required', 'string', 'max:40'],
            'answers'              => ['nullable', 'array'],
        ]);

        QuizResult::create($data);

        return response()->json(['ok' => true]);
    }

    public function tracker() {
        // The tracker aggregates the full prisoners + cases tables through a
        // heavy, year- and state-aware cost model. That data changes only when
        // an admin edits, so the computed payload is cached — keyed on the
        // current year so the rolling 50-year window still refreshes annually.
        // Run `php artisan cache:clear` to force an immediate refresh.
        $payload = Cache::remember(
            'tracker:payload:v2:'.date('Y'),
            now()->addHours(6),
            fn () => $this->computeTrackerPayload(),
        );

        return view('pages.tracker', $payload);
    }

    /**
     * Builds the tracker's aggregate cost dataset (the full set of view
     * variables). Extracted from tracker() so the heavy result can be cached.
     */
    private function computeTrackerPayload(): array {
        // Modern-era cutoff. The pre-WWII archive material is largely
        // labor and anarchist cases whose incarceration day counts and
        // dollar-cost figures are too speculative to mix into a real-time
        // dollar tracker; constrain to 1950→ so the running total tracks
        // the contemporary political-prosecution period the page is
        // actually about.
        // Rolling 50-year window — recomputed when the cache misses so the
        // page always reflects "the past 50 years" of political prosecution
        // rather than a fixed start date that ages out.
        $windowYears = 50;
        $cutoffYear = (int) date('Y') - $windowYears;
        $cutoffDate = $cutoffYear.'-01-01';

        $allPrisoners = Prisoner::all();
        $allCases = PrisonerCase::with('institution')->get();

        // Filter cases to those with an arrest_date on/after the cutoff.
        // A case with no arrest_date is excluded (we can't place it).
        $cases = $allCases->filter(function ($c) use ($cutoffDate) {
            return $c->arrest_date && (string) $c->arrest_date >= $cutoffDate;
        })->values();

        // Filter prisoners to those who have at least one in-scope case.
        $inScopePrisonerIds = $cases->pluck('prisoner_id')->unique()->flip();
        $prisoners = $allPrisoners->filter(fn ($p) => isset($inScopePrisonerIds[$p->id]))->values();

        // ── Cost model ────────────────────────────────────────────────
        // Year-aware, state-aware. See App\Support\IncarcerationCostRates
        // for the underlying data tables (state DOC budgets, BOP Federal
        // Register annual rates, BJS jail averages) and the year-of-
        // incarceration adjustment factor.
        // ─────────────────────────────────────────────────────────────

        $totalDaysImprisoned = \App\Support\ImprisonmentDuration::totalDaysAcrossPrisoners($cases);
        // Unioned per prisoner before summing across them: two open-ended
        // exile rows on one record both run to today, so summing the column
        // straight across the table counts the same days twice. See
        // ExileDuration.
        $totalDaysInExile = (int) $cases->groupBy('prisoner_id')
            ->map(fn ($rows) => ExileDuration::totalDays($rows))
            ->sum();

        $totalPrisoners = $prisoners->count();
        $totalCases = $cases->count();

        // Helper: classify a case into federal / state / local from its
        // institution (preferring the structured institution_id columns,
        // falling back to name regex if state is empty on the row).
        $classify = function ($case): array {
            $inst = $case->institution;
            $name = (string) optional($inst)->name;
            $state = strtoupper((string) optional($inst)->state);

            if ($name !== '' && preg_match('/\b(federal|FCI|USP|ADX|FMC|FDC|MDC|MCC|FCC|U\.S\.\s*Penit|United States Penit|U\.S\. District|Bureau of Prisons|BOP)\b/i', $name)) {
                return ['bucket' => 'federal', 'state' => null];
            }
            if ($name !== '' && preg_match('/\b(county jail|city jail|municipal|holding facility)\b/i', $name)) {
                return ['bucket' => 'local', 'state' => $state ?: null];
            }
            return ['bucket' => 'state', 'state' => $state ?: null];
        };

        $costFederalIncarceration = 0.0;
        $costStateIncarceration   = 0.0;
        $costLocalIncarceration   = 0.0;
        $costOfInvestigation      = 0.0;
        $costOfProsecution        = 0.0;
        $costOfAppeals            = 0.0;
        $federalDays = 0; $stateDays = 0; $localDays = 0;
        $convictedCases = 0;

        // Charge-frequency taxonomy: each entry is [label, regex]. A case
        // can match more than one. We tally how many cases hit each
        // category and the total per-case cost across them, then derive
        // an average cost per case at the end.
        $chargeCats = [
            ['Material support',            '/material\s+support|providing\s+support/i'],
            ['Conspiracy',                  '/conspirac|conspir(e|ed|ing)/i'],
            ['Murder / attempted murder',   '/murder|homicide|manslaughter/i'],
            ['Assault',                     '/assault|battery|aggravated\s+(assault|battery)/i'],
            ['Firearms / weapons',          '/firearm|weapon|unlawful\s+use\s+of\s+a?\s*(weapon|firearm)|possession\s+of\s+a?\s*(weapon|firearm)/i'],
            ['Explosives / bombing',        '/explosive|bomb|incendiary|destructive\s+device|arson/i'],
            ['Seditious conspiracy',        '/sedition|seditious/i'],
            ['Espionage',                   '/espionage|spy|classified|national\s+defense\s+information|18\s*u\.?s\.?c\.?\s*793|794/i'],
            ['Racketeering / RICO',         '/rico|racketeer|continuing\s+criminal\s+enterprise|\bcce\b/i'],
            ['Drug offenses',               '/drug|narcotic|controlled\s+substance|trafficking|distribution\s+of\s+(cocaine|heroin|marijuana)/i'],
            ['Robbery / expropriation',     '/robbery|bank\s+(robbery|expropriat)|armed\s+robbery|expropriat/i'],
            ['Property destruction / sabotage', '/sabotage|destruction\s+of\s+(government\s+)?property|criminal\s+mischief|vandalism/i'],
            ['Kidnapping',                  '/kidnap|abduction|hostage/i'],
            ['Immigration violations',      '/immigration|unlawful\s+(entry|reentry|presence)|visa\s+fraud|harbor(ing)?\s+aliens?/i'],
            ['Fraud / financial',           '/fraud|money\s+launder|embezzle|wire\s+fraud|mail\s+fraud|financial/i'],
            ['False statements / perjury',  '/false\s+statement|perjury|lying\s+to|making\s+false/i'],
            ['Obstruction / contempt',      '/obstruct|contempt|interfer(e|ing)\s+with/i'],
            ['Trespass / disorderly',       '/trespass|disorderly|unlawful\s+assembly|disturbing\s+the\s+peace/i'],
            ['Tax violations',              '/tax\s+(evasion|fraud|violation)|failure\s+to\s+(file|pay)\s+tax/i'],
            ['Theft / stolen property',     '/theft|larceny|stolen\s+property|burglar|receiving\s+stolen/i'],
        ];
        $chargeCount = array_fill_keys(array_column($chargeCats, 0), 0);
        $chargeCost  = array_fill_keys(array_column($chargeCats, 0), 0.0);

        foreach ($cases as $c) {
            $cls = $classify($c);
            $bucket = $cls['bucket'];
            $state  = $cls['state'];

            $days = (int) ($c->imprisoned_for_days ?? 0);
            if ($days > 0) {
                if ($bucket === 'federal')   $federalDays += $days;
                elseif ($bucket === 'local') $localDays   += $days;
                else                          $stateDays   += $days;
            }

            // Anchor dates: incarceration_date → release_date (or today),
            // falling back to arrest_date if incarceration_date is null.
            $start = $c->incarceration_date ?: $c->arrest_date;
            $end   = $c->release_date ?: null;

            $startC = $start ? Carbon::parse($start) : null;
            $endC   = $end   ? Carbon::parse($end)   : null;

            $incCost = IncarcerationCostRates::costForPeriod($bucket, $state, $startC, $endC, $days);
            if ($bucket === 'federal')   $costFederalIncarceration += $incCost;
            elseif ($bucket === 'local') $costLocalIncarceration   += $incCost;
            else                         $costStateIncarceration   += $incCost;

            // Prosecution + appeals priced by charge tier (capital,
            // complex federal, federal felony, state violent / non-
            // violent, misdemeanor) AND year so an old or low-severity
            // case isn't billed at modern capital-trial rates.
            $arrestYear = $c->arrest_date ? (int) Carbon::parse($c->arrest_date)->year : (int) date('Y');
            $costOfInvestigation += IncarcerationCostRates::investigationCost($bucket, $c->charges, $c->sentence, $arrestYear);
            $costOfProsecution += IncarcerationCostRates::prosecutionCost($bucket, $c->charges, $c->sentence, $arrestYear);

            $convicted = (string) ($c->convicted ?? '') !== ''
                || (string) ($c->plead ?? '') !== ''
                || (string) ($c->sentence ?? '') !== '';
            $caseAppeals = 0.0;
            if ($convicted) {
                $caseAppeals = IncarcerationCostRates::appealsCost($bucket, $c->charges, $c->sentence, $arrestYear);
                $costOfAppeals += $caseAppeals;
                $convictedCases++;
            }

            // Per-charge average uses the PROSECUTION cost only — what the
            // trial itself costs — not the all-in incarceration total.
            $caseProsecution = IncarcerationCostRates::prosecutionCost($bucket, $c->charges, $c->sentence, $arrestYear);

            // Tally charge categories this case matches.
            $chargeText = (string) ($c->charges ?? '');
            if ($chargeText !== '') {
                foreach ($chargeCats as [$label, $regex]) {
                    if (preg_match($regex, $chargeText)) {
                        $chargeCount[$label]++;
                        $chargeCost[$label] += $caseProsecution;
                    }
                }
            }
        }

        // Build the charge-frequency dataset: count + average per-case cost,
        // sorted by count descending, dropping any category with no hits.
        $chargeStats = [];
        foreach ($chargeCount as $label => $count) {
            if ($count <= 0) continue;
            $chargeStats[] = [
                'label' => $label,
                'count' => $count,
                'avgCost' => (int) round($chargeCost[$label] / $count),
            ];
        }
        usort($chargeStats, fn ($a, $b) => $b['count'] <=> $a['count']);
        $chargeStats = array_slice($chargeStats, 0, 20);
        $maxChargeCount = $chargeStats ? max(array_column($chargeStats, 'count')) : 1;

        // ── Affiliation over time ─────────────────────────────────────
        // Sum the prosecution-and-incarceration cost of each prisoner
        // into the year of their earliest documented arrest, grouped by
        // purported affiliation (counted once per affiliation they're
        // tagged with). Uses the same cost model as the by-ideology
        // breakdown, so this chart reads in dollars, not head count.
        $casesByPrisonerTmp = $cases->groupBy('prisoner_id');
        $affByYear = [];   // [affiliation][year] => dollars
        $affTotals = [];   // [affiliation]       => dollars
        foreach ($prisoners as $p) {
            $set = $casesByPrisonerTmp->get($p->id);
            $arrest = $set?->whereNotNull('arrest_date')->min('arrest_date');
            if (! $arrest) continue;
            $yr = (int) Carbon::parse($arrest)->year;

            $cost = 0.0;
            foreach ($set ?? collect() as $c) {
                $cls    = $classify($c);
                $days   = (int) ($c->imprisoned_for_days ?? 0);
                $start  = $c->incarceration_date ?: $c->arrest_date;
                $startC = $start ? Carbon::parse($start) : null;
                $endC   = $c->release_date ? Carbon::parse($c->release_date) : null;
                $cost  += IncarcerationCostRates::costForPeriod($cls['bucket'], $cls['state'], $startC, $endC, $days);
                $arrestYear = $c->arrest_date ? (int) Carbon::parse($c->arrest_date)->year : (int) date('Y');
                $cost  += IncarcerationCostRates::investigationCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                $cost  += IncarcerationCostRates::prosecutionCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                if ((string) ($c->convicted ?? '') !== '' || (string) ($c->plead ?? '') !== '' || (string) ($c->sentence ?? '') !== '') {
                    $cost += IncarcerationCostRates::appealsCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                }
            }
            $cost = (int) round($cost);
            if ($cost <= 0) continue;

            $affs = array_values(array_filter((array) $p->affiliation, fn ($a) => trim((string) $a) !== ''));
            if (empty($affs)) $affs = ['Unaffiliated'];
            foreach ($affs as $aff) {
                $aff = trim((string) $aff);
                $affByYear[$aff][$yr] = ($affByYear[$aff][$yr] ?? 0) + $cost;
                $affTotals[$aff] = ($affTotals[$aff] ?? 0) + $cost;
            }
        }
        arsort($affTotals);
        $topAffiliations = array_slice(array_keys($affTotals), 0, 6);
        $affYears = range($cutoffYear, (int) date('Y'));
        $affiliationSeries = [];
        foreach ($topAffiliations as $aff) {
            $row = [];
            foreach ($affYears as $y) {
                $row[] = $affByYear[$aff][$y] ?? 0;
            }
            $affiliationSeries[] = [
                'label' => $aff,
                'total' => $affTotals[$aff],
                'data'  => $row,
            ];
        }

        // Round once for display; internal sums kept as floats above.
        $costFederalIncarceration = (int) round($costFederalIncarceration);
        $costStateIncarceration   = (int) round($costStateIncarceration);
        $costLocalIncarceration   = (int) round($costLocalIncarceration);
        $costOfInvestigation      = (int) round($costOfInvestigation);
        $costOfProsecution        = (int) round($costOfProsecution);
        $costOfAppeals            = (int) round($costOfAppeals);

        $totalCost = $costFederalIncarceration + $costStateIncarceration + $costLocalIncarceration
                   + $costOfInvestigation + $costOfProsecution + $costOfAppeals;

        // Ongoing daily burn — the per-day incarceration cost of every
        // currently in-custody prisoner at this year's rate. Used by
        // the live ticker on the hero counter so the total keeps
        // climbing in real time at a verifiable rate.
        $thisYear = (int) date('Y');
        $dailyOngoingCost = 0.0;
        $prisonerById = $prisoners->keyBy('id');
        foreach ($cases as $c) {
            $p = $prisonerById->get($c->prisoner_id);
            if (! $p || ! $p->in_custody) continue;
            if ($c->release_date) continue; // case has ended
            $cls = $classify($c);
            $rate = match ($cls['bucket']) {
                'federal' => IncarcerationCostRates::federalDaily($thisYear),
                'local'   => IncarcerationCostRates::localDaily($thisYear),
                default   => IncarcerationCostRates::stateDaily($cls['state'], $thisYear),
            };
            $dailyOngoingCost += $rate;
        }
        $perSecondOngoingCost = $dailyOngoingCost / 86400.0;

        // Bubbles in the middle of the page — sorted descending for visual hierarchy.
        $costBubbles = collect([
            ['label' => 'Federal incarceration', 'value' => $costFederalIncarceration, 'shade' => 'a'],
            ['label' => 'State incarceration',   'value' => $costStateIncarceration,   'shade' => 'b'],
            ['label' => 'Local jail time',       'value' => $costLocalIncarceration,   'shade' => 'c'],
            ['label' => 'Investigations',        'value' => $costOfInvestigation,      'shade' => 'f'],
            ['label' => 'Prosecution',           'value' => $costOfProsecution,        'shade' => 'd'],
            ['label' => 'Appeals & post-conviction', 'value' => $costOfAppeals,        'shade' => 'e'],
        ])->where('value', '>', 0)->sortByDesc('value')->values();

        // CAP-style "where the money goes" cards — same six buckets, with
        // explanatory copy and an emblem key for the diamond artwork.
        $costCards = collect([
            ['key' => 'federal', 'label' => 'Federal detention', 'value' => $costFederalIncarceration,
             'blurb' => "Days served in Bureau of Prisons custody, priced year by year at the BOP's own published per-inmate rate. Federal political cases — espionage, material support, RICO conspiracy — carry the longest sentences and the steepest daily cost."],
            ['key' => 'state', 'label' => 'State detention', 'value' => $costStateIncarceration,
             'blurb' => "Time in state prison systems, priced at each state's annual per-prisoner cost adjusted to the year served. Most movement-era convictions — Black Panther, AIM, Puerto Rican independentista — ran through state custody."],
            ['key' => 'local', 'label' => 'Local & county jails', 'value' => $costLocalIncarceration,
             'blurb' => 'Pretrial detention and short sentences in county and city jails — the most common first stop for protesters and organizers, priced at the national per-inmate local-jail rate.'],
            ['key' => 'investigation', 'label' => 'Investigations', 'value' => $costOfInvestigation,
             'blurb' => 'The surveillance that precedes the charge: FBI field work, Joint Terrorism Task Force stings, COINTELPRO-style infiltration. Years of informants, wiretaps, and grand juries, billed before a single day is served.'],
            ['key' => 'prosecution', 'label' => 'Prosecution & court costs', 'value' => $costOfProsecution,
             'blurb' => 'The trial itself — prosecutors, expert witnesses, and court time. A capital conspiracy case costs orders of magnitude more than a trespassing charge, so every case is tiered by charge severity.'],
            ['key' => 'appeals', 'label' => 'Appeals & post-conviction', 'value' => $costOfAppeals,
             'blurb' => 'Appellate and habeas litigation after conviction — the years of motions, briefs, and federal review that follow a political sentence.'],
        ])->where('value', '>', 0)->sortByDesc('value')->values();

        $costOfIncarceration = $costFederalIncarceration + $costStateIncarceration + $costLocalIncarceration;

        $inCustody = $prisoners->where('in_custody', true)->count();
        $inExile = $prisoners->where('in_exile', true)->count();
        $released = $prisoners->where('released', true)->count();
        $awaitingTrial = $prisoners->where('awaiting_trial', true)->count();

        // Cost by ideology — sum each prisoner's full case cost into
        // every ideology they're tagged with, sort descending.
        $casesByPrisoner = $cases->groupBy('prisoner_id');
        $costByIdeology = [];
        foreach ($prisoners as $p) {
            $set = $casesByPrisoner->get($p->id) ?? collect();
            $cost = 0.0;
            foreach ($set as $c) {
                $cls = $classify($c);
                $days = (int) ($c->imprisoned_for_days ?? 0);
                $start = $c->incarceration_date ?: $c->arrest_date;
                $startC = $start ? Carbon::parse($start) : null;
                $endC   = $c->release_date ? Carbon::parse($c->release_date) : null;
                $cost  += IncarcerationCostRates::costForPeriod($cls['bucket'], $cls['state'], $startC, $endC, $days);
                $arrestYear = $c->arrest_date ? (int) Carbon::parse($c->arrest_date)->year : (int) date('Y');
                $cost  += IncarcerationCostRates::investigationCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                $cost  += IncarcerationCostRates::prosecutionCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                if ((string) ($c->convicted ?? '') !== '' || (string) ($c->plead ?? '') !== '' || (string) ($c->sentence ?? '') !== '') {
                    $cost += IncarcerationCostRates::appealsCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                }
            }
            if (! $cost) continue;
            foreach (((array) $p->ideologies) ?: ['Unclassified'] as $ideology) {
                $costByIdeology[$ideology] = ($costByIdeology[$ideology] ?? 0) + (int) round($cost);
            }
        }
        arsort($costByIdeology);
        $costByIdeology = array_slice($costByIdeology, 0, 10, true);

        // Active cases — currently incarcerated prisoners with their case
        $activeCases = $prisoners->where('in_custody', true)
            ->sortByDesc(fn ($p) => $casesByPrisoner->get($p->id)?->min('arrest_date') ?? '')
            ->take(8)
            ->values();

        // Per-prisoner cost for the active-cards (year-aware, state-aware).
        $activeCaseCosts = [];
        foreach ($activeCases as $p) {
            $set = $casesByPrisoner->get($p->id) ?? collect();
            $cost = 0.0;
            foreach ($set as $c) {
                $cls   = $classify($c);
                $days  = (int) ($c->imprisoned_for_days ?? 0);
                $start = $c->incarceration_date ?: $c->arrest_date;
                $startC = $start ? Carbon::parse($start) : null;
                $endC   = $c->release_date ? Carbon::parse($c->release_date) : null;
                $cost += IncarcerationCostRates::costForPeriod($cls['bucket'], $cls['state'], $startC, $endC, $days);
                $arrestYear = $c->arrest_date ? (int) Carbon::parse($c->arrest_date)->year : (int) date('Y');
                $cost += IncarcerationCostRates::investigationCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                $cost += IncarcerationCostRates::prosecutionCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                if ((string) ($c->convicted ?? '') !== '' || (string) ($c->plead ?? '') !== '' || (string) ($c->sentence ?? '') !== '') {
                    $cost += IncarcerationCostRates::appealsCost($cls['bucket'], $c->charges, $c->sentence, $arrestYear);
                }
            }
            $activeCaseCosts[$p->id] = (int) round($cost);
        }

        // firstYear is fixed at the cutoff — every figure on the page is
        // scoped to cases on or after this date.
        $firstYear = $cutoffYear;

        // Then & Now comparison: earliest documented case (oldest arrest)
        // vs the most recent active case. Both used as visual anchors.
        $earliestCase = $cases->whereNotNull('arrest_date')->sortBy('arrest_date')->first();
        $earliestPrisoner = $earliestCase ? $prisoners->firstWhere('id', $earliestCase->prisoner_id) : null;
        $newestActiveCase = $cases->whereNotNull('arrest_date')->sortByDesc('arrest_date')
            ->first(fn ($c) => optional($prisoners->firstWhere('id', $c->prisoner_id))->in_custody);
        $newestActivePrisoner = $newestActiveCase ? $prisoners->firstWhere('id', $newestActiveCase->prisoner_id) : null;

        // Hero photo strip: 4 prisoners with photos, preferring the
        // currently incarcerated (so the page leads with active cases).
        $heroPrisoners = $prisoners
            ->filter(fn ($p) => ! empty($p->photo))
            ->sortByDesc('in_custody')
            ->take(4)
            ->values();

        // Display-only sample rates surfaced in the methodology copy.
        $methodFedRateRange  = ['min' => 36, 'max' => (int) round(IncarcerationCostRates::federalDaily((int) date('Y'))), 'minYear' => 1985, 'maxYear' => (int) date('Y')];

        return compact(
            'totalDaysImprisoned', 'totalDaysInExile',
            'inCustody', 'inExile', 'released', 'awaitingTrial',
            'costByIdeology', 'activeCases', 'totalPrisoners', 'totalCases', 'firstYear',
            'casesByPrisoner', 'activeCaseCosts',
            'earliestCase', 'earliestPrisoner', 'newestActiveCase', 'newestActivePrisoner',
            'heroPrisoners',
            'costOfIncarceration', 'costOfProsecution', 'totalCost',
            'costFederalIncarceration', 'costStateIncarceration', 'costLocalIncarceration',
            'costOfInvestigation', 'costOfAppeals',
            'dailyOngoingCost', 'perSecondOngoingCost',
            'costBubbles', 'costCards', 'windowYears', 'methodFedRateRange',
            'chargeStats', 'maxChargeCount',
            'affiliationSeries', 'affYears',
            'federalDays', 'stateDays', 'localDays',
        );
    }

    public function faq() {
        return view('pages.faq');
    }

    /**
     * Slugs of "pointer" articles: cards that appear in the news grid for
     * discovery but send the reader to a standalone feature page rather
     * than a /news/{slug} story.
     */
    private const FEATURE_REDIRECTS = [
        'the-price-of-political-prosecution' => '/feature-political-prisoner-cost',
        'under-cover-of-war' => '/iran-war-political-prisoners',
        'detained-for-dissent' => '/student-visa-revocations-and-ice-arrests',
        'the-data-center-revolt' => '/data-center-cases',
        'transnational-repression-report' => '/transnational-repression',
    ];

    /** slug => [full name, abbreviation, ...extra stored variants] */
    public const STATES = [
        'alabama' => ['Alabama', 'AL'], 'alaska' => ['Alaska', 'AK'], 'arizona' => ['Arizona', 'AZ'],
        'arkansas' => ['Arkansas', 'AR'], 'california' => ['California', 'CA'], 'colorado' => ['Colorado', 'CO'],
        'connecticut' => ['Connecticut', 'CT'], 'delaware' => ['Delaware', 'DE'],
        'district-of-columbia' => ['District of Columbia', 'DC', 'Washington, D.C.', 'Washington DC'],
        'florida' => ['Florida', 'FL'], 'georgia' => ['Georgia', 'GA'], 'hawaii' => ['Hawaii', 'HI'],
        'idaho' => ['Idaho', 'ID'], 'illinois' => ['Illinois', 'IL'], 'indiana' => ['Indiana', 'IN'],
        'iowa' => ['Iowa', 'IA'], 'kansas' => ['Kansas', 'KS'], 'kentucky' => ['Kentucky', 'KY'],
        'louisiana' => ['Louisiana', 'LA'], 'maine' => ['Maine', 'ME'], 'maryland' => ['Maryland', 'MD'],
        'massachusetts' => ['Massachusetts', 'MA'], 'michigan' => ['Michigan', 'MI'],
        'minnesota' => ['Minnesota', 'MN'], 'mississippi' => ['Mississippi', 'MS'],
        'missouri' => ['Missouri', 'MO'], 'montana' => ['Montana', 'MT'], 'nebraska' => ['Nebraska', 'NE'],
        'nevada' => ['Nevada', 'NV'], 'new-hampshire' => ['New Hampshire', 'NH'],
        'new-jersey' => ['New Jersey', 'NJ'], 'new-mexico' => ['New Mexico', 'NM'],
        'new-york' => ['New York', 'NY'], 'north-carolina' => ['North Carolina', 'NC'],
        'north-dakota' => ['North Dakota', 'ND'], 'ohio' => ['Ohio', 'OH'], 'oklahoma' => ['Oklahoma', 'OK'],
        'oregon' => ['Oregon', 'OR'], 'pennsylvania' => ['Pennsylvania', 'PA'],
        'rhode-island' => ['Rhode Island', 'RI'], 'south-carolina' => ['South Carolina', 'SC'],
        'south-dakota' => ['South Dakota', 'SD'], 'tennessee' => ['Tennessee', 'TN'],
        'texas' => ['Texas', 'TX'], 'utah' => ['Utah', 'UT'], 'vermont' => ['Vermont', 'VT'],
        'virginia' => ['Virginia', 'VA'], 'washington' => ['Washington', 'WA'],
        'west-virginia' => ['West Virginia', 'WV'], 'wisconsin' => ['Wisconsin', 'WI'],
        'wyoming' => ['Wyoming', 'WY'], 'puerto-rico' => ['Puerto Rico', 'PR'],
    ];

    public function state(string $slug) {
        $variants = self::STATES[$slug] ?? null;
        if ($variants === null) {
            abort(404);
        }
        $name = $variants[0];

        $base = Prisoner::whereIn('state', $variants);

        $stats = [
            'total' => (clone $base)->count(),
            'in_custody' => (clone $base)->where('in_custody', true)->count(),
            'released' => (clone $base)->where('released', true)->count(),
            'awaiting' => (clone $base)->where('awaiting_trial', true)->count(),
        ];

        // Photo-bearing entries lead so the grid opens strong.
        $prisoners = (clone $base)
            ->orderByRaw("(photo IS NOT NULL AND photo != '') DESC")
            ->orderByRaw('in_custody DESC')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(24, ['id', 'name', 'slug', 'photo', 'state', 'description',
                'in_custody', 'released', 'awaiting_trial', 'in_exile', 'era']);

        $eras = (clone $base)
            ->whereNotNull('era')->where('era', '!=', '')
            ->selectRaw('era, count(*) as n')
            ->groupBy('era')->orderByDesc('n')->limit(6)->get();

        $institutions = Institution::whereIn('state', $variants)
            ->select('institutions.*')
            ->selectRaw('(select count(*) from prisoner_cases where prisoner_cases.institution_id = institutions.id) as cases_count')
            ->orderByDesc('cases_count')
            ->limit(8)->get()
            ->filter(fn ($i) => $i->cases_count > 0)
            ->values();

        $shapes = json_decode((string) file_get_contents(database_path('data/state-shapes.json')), true) ?: [];

        // prev / next state for footer navigation
        $slugs = array_keys(self::STATES);
        $idx = (int) array_search($slug, $slugs, true);
        $prev = $slugs[($idx - 1 + count($slugs)) % count($slugs)];
        $next = $slugs[($idx + 1) % count($slugs)];

        return view('pages.state', [
            'slug' => $slug,
            'name' => $name,
            'stats' => $stats,
            'prisoners' => $prisoners,
            'eras' => $eras,
            'institutions' => $institutions,
            'shape' => $shapes[$name] ?? null,
            'prevState' => ['slug' => $prev, 'name' => self::STATES[$prev][0]],
            'nextState' => ['slug' => $next, 'name' => self::STATES[$next][0]],
        ]);
    }

    public function author(string $slug) {
        if ($slug === 'nppc-communications') {
            return redirect('/author/national-political-prisoner-coalition', 301);
        }

        $author = Author::where('slug', $slug)->firstOrFail();

        $articles = $author->articles()
            ->whereNotNull('published_at')
            ->with('category')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('pages.author', [
            'author' => $author,
            'articles' => $articles,
        ]);
    }

    public function article(string $type, string $slug) {
        if ($target = self::FEATURE_REDIRECTS[$slug] ?? null) {
            return redirect($target);
        }

        $article = Article::getBySlug($slug);

        if (! $article) {
            abort(404);
        }

        // The canonical URL is /{category}/{slug}. Redirect a mismatched prefix
        // — e.g. a legacy /news/... link for an article now in another category —
        // to the canonical URL.
        if (ltrim($article->url, '/') !== $type.'/'.$slug) {
            return redirect($article->url, 301);
        }

        // Related articles: rank other published pieces by how related they
        // actually are to this one — shared tags first, then overlapping title
        // keywords — and only fall back to the latest articles to fill any
        // empty slots. (The site uses a single category, so category can't
        // drive relatedness; the old category-then-latest logic collapsed to
        // "the most recent articles".) Capped at 3.
        $limit = 3;
        $base  = fn () => Article::with('category')
            ->whereNotNull('published_at')
            ->where('id', '!=', $article->id)
            ->orderByDesc('published_at');

        // 1) Articles that share tags with this one, most shared tags first.
        $related  = collect();
        $tagNames = $article->tags->pluck('name');
        if ($tagNames->isNotEmpty()) {
            $related = Article::withAnyTags($tagNames->all())
                ->whereNotNull('published_at')
                ->where('id', '!=', $article->id)
                ->with(['category', 'tags'])
                ->get()
                ->sortByDesc(fn ($a) => $a->tags->pluck('name')->intersect($tagNames)->count())
                ->take($limit)
                ->values();
        }

        // 2) Top up with articles whose titles share significant keywords.
        if ($related->count() < $limit) {
            $stop = ['from', 'with', 'this', 'that', 'have', 'been', 'will', 'about', 'their', 'there',
                'which', 'would', 'could', 'after', 'into', 'they', 'them', 'when', 'what', 'over', 'than',
                'then', 'your', 'were', 'where', 'while', 'these', 'those', 'being', 'because', 'against',
                'through', 'during', 'before', 'between', 'under', 'more', 'most', 'some', 'such', 'only',
                'also', 'just', 'much', 'many', 'other', 'another', 'said', 'says', 'told', 'according'];
            $keywords = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($article->title), -1, PREG_SPLIT_NO_EMPTY))
                ->filter(fn ($w) => mb_strlen($w) >= 4 && ! in_array($w, $stop, true))
                ->unique()
                ->values();

            if ($keywords->isNotEmpty()) {
                $pool = $base()
                    ->whereNotIn('id', $related->pluck('id')->all())
                    ->where(function ($w) use ($keywords) {
                        foreach ($keywords as $kw) {
                            $w->orWhere('title', 'like', '%'.$kw.'%');
                        }
                    })
                    ->limit(40)
                    ->get()
                    ->sortByDesc(fn ($a) => $keywords->filter(fn ($kw) => str_contains(mb_strtolower($a->title), $kw))->count());

                $related = $related->concat($pool->take($limit - $related->count()))->values();
            }
        }

        // 3) Final fallback: the latest other articles, to fill any remaining slots.
        if ($related->count() < $limit) {
            $related = $related->concat(
                $base()->whereNotIn('id', $related->pluck('id')->all())
                    ->limit($limit - $related->count())
                    ->get()
            )->values();
        }

        return view('article', compact('article', 'related'));
    }

    public function search(Request $request) {
        $q = trim($request->input('q', ''));

        if (! $q) {
            return view('pages.search', ['query' => '', 'results' => []]);
        }

        $results = [];

        // Search articles
        $articles = Article::whereNotNull('published_at')
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get();

        foreach ($articles as $article) {
            $results[] = [
                'type'  => 'Article',
                'title' => $article->title,
                'url'   => $article->url,
                'excerpt' => mb_substr(html_entity_decode(strip_tags($article->body ?? ''), ENT_QUOTES), 0, 200),
            ];
        }

        // Search pages
        $pages = Page::where('title', 'like', "%{$q}%")
            ->orWhere('body', 'like', "%{$q}%")
            ->limit(20)
            ->get();

        foreach ($pages as $page) {
            $results[] = [
                'type'  => 'Page',
                'title' => $page->title,
                'url'   => $page->url,
                'excerpt' => mb_substr(html_entity_decode(strip_tags($page->body ?? ''), ENT_QUOTES), 0, 200),
            ];
        }

        // Search prisoners. Accent-insensitive so "Maria Cueto" matches
        // "María Cueto" (and the reverse).
        $prisoners = AccentInsensitiveSearch::allWords(
            Prisoner::query(),
            ['name', 'aka', 'first_name', 'middle_name', 'last_name', 'description'],
            $q,
        )
            ->limit(20)
            ->get();

        foreach ($prisoners as $prisoner) {
            $results[] = [
                'type'  => 'Prisoner',
                'title' => $prisoner->name,
                'url'   => '/prisoner/'.($prisoner->slug ?: $prisoner->id),
                'excerpt' => mb_substr($prisoner->description ?? '', 0, 200),
            ];
        }

        // Search FAQs
        $faqs = Faq::where('question', 'like', "%{$q}%")
            ->orWhere('answer', 'like', "%{$q}%")
            ->limit(10)
            ->get();

        foreach ($faqs as $faq) {
            $results[] = [
                'type'  => 'FAQ',
                'title' => $faq->question,
                'url'   => '/faq',
                'excerpt' => mb_substr(html_entity_decode(strip_tags($faq->answer ?? ''), ENT_QUOTES), 0, 200),
            ];
        }

        // Search static pages by name
        $staticPages = [
            'history'           => ['title' => 'History', 'url' => '/history'],
            'volunteer'         => ['title' => 'Volunteer', 'url' => '/volunteer'],
            'prisoner outreach' => ['title' => 'Prisoner Outreach', 'url' => '/prisoner-outreach'],
            'staff'             => ['title' => 'Staff', 'url' => '/staff'],
            'board of directors' => ['title' => 'Board of Directors', 'url' => '/board-of-directors'],
            'annual report'     => ['title' => 'Annual Report', 'url' => '/annual-report'],
            'map'               => ['title' => 'Map', 'url' => '/map'],
            'faq'               => ['title' => 'FAQ', 'url' => '/faq'],
            'donate'            => ['title' => 'Donate', 'url' => '/donate'],
            'contact'           => ['title' => 'Contact Us', 'url' => '/contact'],
            'database'          => ['title' => 'Prisoner Database', 'url' => '/database'],
            'news'              => ['title' => 'News', 'url' => '/news'],
        ];

        foreach ($staticPages as $keyword => $page) {
            if (stripos($keyword, $q) !== false) {
                $results[] = [
                    'type'    => 'Page',
                    'title'   => $page['title'],
                    'url'     => $page['url'],
                    'excerpt' => '',
                ];
            }
        }

        return view('pages.search', ['query' => $q, 'results' => $results]);
    }

    public function podcast() {
        $episodes = \App\Models\PodcastEpisode::published()->orderBy('sort_order')->get();

        return view('pages.podcast', compact('episodes'));
    }

    public function archiveView(\App\Models\ArchiveRecord $record) {
        if (! $record->file_url) {
            abort(404);
        }

        return view('pages.archive-view', compact('record'));
    }

    public function petitionsIndex(Request $request) {
        // Featured spot: the newest published petition with an image
        // (falling back to the newest overall). It renders in the hero band
        // and is excluded from the paginated grid below.
        $featured = \App\Models\Petition::where('published', true)
            ->withCount('signatures')
            ->whereNotNull('image')
            ->orderByDesc('created_at')
            ->first()
            ?? \App\Models\Petition::where('published', true)
                ->withCount('signatures')
                ->orderByDesc('created_at')
                ->first();

        $sort = $request->query('sort', 'newest');
        $state = $request->query('state');

        $petitions = \App\Models\Petition::where('published', true)
            ->when($featured, fn ($q) => $q->where('id', '!=', $featured->id))
            ->when($state, fn ($q) => $q->where('state', $state))
            ->withCount('signatures')
            ->when($sort === 'oldest', fn ($q) => $q->orderBy('created_at'))
            ->when($sort === 'most-signed', fn ($q) => $q->orderByDesc('signatures_count'))
            ->when($sort === 'closest-to-goal', fn ($q) => $q->orderByRaw('signatures_count / GREATEST(signature_goal, 1) DESC'))
            ->when(! in_array($sort, ['oldest', 'most-signed', 'closest-to-goal']), fn ($q) => $q->orderByDesc('created_at'))
            ->paginate(12)
            ->appends($request->query());

        // States present across published petitions, for the filter dropdown.
        $states = \App\Models\Petition::where('published', true)
            ->whereNotNull('state')
            ->distinct()
            ->orderBy('state')
            ->pluck('state');

        return view('pages.petitions-index', compact('petitions', 'featured', 'states', 'sort', 'state'));
    }

    public function petitionPage(string $slug) {
        $petition = \App\Models\Petition::where('slug', $slug)->where('published', true)->firstOrFail();
        $recentSigners = $petition->signatures()->where('display_publicly', true)->latest()->limit(5)->get();

        return view('pages.petition', compact('petition', 'recentSigners'));
    }

    public function petitionSign(Request $request, string $slug) {
        $petition = \App\Models\Petition::where('slug', $slug)->where('published', true)->firstOrFail();

        $request->validate([
            'first_name'       => 'required|string|max:100',
            'last_name'        => 'required|string|max:100',
            'email'            => 'required|email|max:255',
            'city'             => 'nullable|string|max:100',
            'state'            => 'nullable|string|max:100',
            'zip_code'         => 'nullable|string|max:20',
            'phone'            => 'nullable|string|max:30',
            'custom_message'   => 'nullable|string|max:2000',
            'display_publicly' => 'nullable|boolean',
            'email_optin'      => 'nullable|boolean',
        ]);

        // Prevent duplicate signatures from the same email
        $alreadySigned = \App\Models\PetitionSignature::where('petition_id', $petition->id)
            ->where('email', $request->input('email'))
            ->exists();

        if ($alreadySigned) {
            return redirect("/petition/{$slug}?signed=true");
        }

        \App\Models\PetitionSignature::create([
            'petition_id'      => $petition->id,
            'first_name'       => $request->input('first_name'),
            'last_name'        => $request->input('last_name'),
            'email'            => $request->input('email'),
            'city'             => $request->input('city'),
            'state'            => $request->input('state'),
            'zip_code'         => $request->input('zip_code'),
            'phone'            => $request->input('phone'),
            'custom_message'   => $request->input('custom_message'),
            'display_publicly' => $request->boolean('display_publicly'),
        ]);

        // Subscribe to the newsletter if the signer opted in.
        if ($request->boolean('email_optin')) {
            \App\Models\EmailSubscriber::firstOrCreate(
                ['email' => $request->input('email')],
                ['status' => 'active']
            );
        }

        return redirect("/petition/{$slug}?signed=true");
    }

    public function prisoner(string $slug) {
        // Try slug first, fall back to ID for backwards compatibility
        $prisoner = Prisoner::with(['cases.institution'])->where('slug', $slug)->first()
            ?? Prisoner::with(['cases.institution'])->findOrFail($slug);

        // Redirect old ID URLs to slug URLs for SEO
        if ($prisoner->slug && $slug !== $prisoner->slug) {
            return redirect('/prisoner/'.$prisoner->slug, 301);
        }

        $related = $this->relatedPrisoners($prisoner);

        return view('pages.prisoner', compact('prisoner', 'related'));
    }

    /**
     * The portrait wall: every prisoner in the archive who has a photograph.
     *
     * Deliberately not a second /database. That page is the Vue data browser
     * — cards, counters, a table of facts. This one is only the faces, at a
     * size where a era or a movement can be taken in at a glance, and it
     * exists because 2,198 photographs are otherwise reachable only one
     * record at a time.
     *
     * Filtering is server-side on plain query parameters, so the page works
     * without JavaScript, deep-links, and can be shared. Options are built
     * from the photographed set rather than the whole table, so a filter can
     * never lead to an empty wall.
     */
    public function icons(Request $request)
    {
        $base = Prisoner::whereNotNull('photo')->where('photo', '!=', '');

        // Facet options, counted over the photographed set only.
        $options = Cache::remember('icons.options.v1', 3600, function () {
            $rows = Prisoner::whereNotNull('photo')->where('photo', '!=', '')
                ->get(['era', 'state', 'ideologies', 'affiliation']);

            $tally = function ($values) {
                $c = [];
                foreach ($values as $v) {
                    foreach ((array) $v as $item) {
                        $item = is_string($item) ? trim($item) : '';
                        if ($item !== '') {
                            $c[$item] = ($c[$item] ?? 0) + 1;
                        }
                    }
                }
                ksort($c);

                return $c;
            };

            return [
                'era' => $tally($rows->pluck('era')),
                'ideology' => $tally($rows->pluck('ideologies')),
                'affiliation' => $tally($rows->pluck('affiliation')),
                'state' => $tally($rows->pluck('state')),
            ];
        });

        // Selected values, kept only when they are real options — a junk
        // query string filters nothing rather than emptying the page.
        $selected = [];
        foreach (['era', 'ideology', 'affiliation', 'state'] as $facet) {
            $value = trim((string) $request->query($facet, ''));
            $selected[$facet] = ($value !== '' && isset($options[$facet][$value])) ? $value : null;
        }

        if ($selected['era']) {
            $base->where('era', $selected['era']);
        }

        if ($selected['state']) {
            $base->where('state', $selected['state']);
        }

        // ideologies and affiliation are JSON arrays. LIKE on the encoded
        // value keeps this portable across SQLite and MySQL, the same call
        // made for the related-cases query.
        foreach (['ideology' => 'ideologies', 'affiliation' => 'affiliation'] as $facet => $column) {
            if ($selected[$facet]) {
                $needle = str_replace(['%', '_'], ['\%', '\_'], json_encode($selected[$facet]));
                $base->where($column, 'like', '%'.$needle.'%');
            }
        }

        // "Show new": the most recently added records, by the same two-week
        // window the tiles use for their badge. Rolling, not a stored flag —
        // a record ages out of it on its own.
        //
        // The date is when the row was written here, not when the person was
        // arrested, so a bulk import can light up the badge on everything it
        // touches. Two weeks keeps that blast radius small.
        $newSince = Carbon::now()->subDays(14);
        $showNew = $request->boolean('new');

        if ($showNew) {
            $base->where('created_at', '>=', $newSince);
        }

        $prisoners = $base
            ->orderByRaw('case when created_at >= ? then 0 else 1 end', [$newSince])
            ->orderBy('sort_order')
            ->paginate(180)
            ->withQueryString();

        return view('pages.icons', [
            'prisoners' => $prisoners,
            'options' => $options,
            'selected' => $selected,
            'showNew' => $showNew,
            'newSince' => $newSince,
            'total' => $prisoners->total(),
        ]);
    }

    /**
     * How many related tiles this particular page should show.
     *
     * Not a fixed number. A page with one strong cohort behind it (a mass
     * trial, an organization) has more worth showing than a lone federal
     * defendant, so the allowance scales with how strong the best match is,
     * and the count is then however many actually reach the top of the
     * ranking rather than a quota filled to the brim.
     *
     * Measured across all 8,598 records: 89% land between 4 and 12, ~3%
     * legitimately go above it where a tight cohort earns it, and the rest
     * fall short because the record genuinely has few neighbors. Twelve is
     * not a ceiling — the observed maximum is 15 — and 18 is only a
     * page-weight guard, not a design limit.
     */
    private function relatedCount($ranked): int
    {
        if ($ranked->isEmpty()) {
            return 0;
        }

        $top = (int) $ranked->first()->related_score;

        // Allowance grows with the strength of the closest relationship.
        $allowance = max(4, min(18, (int) round($top * 1.1)));

        // Everyone at the top of the ranking, plus the point below it, so a
        // cohort is not split from the person one shared fact behind them.
        $near = $ranked->filter(fn ($p) => $p->related_score >= max(3, $top - 1))->count();

        // Show at least four when four exist, never more than the allowance.
        return min(max($near, min(4, $ranked->count())), $allowance);
    }

    /**
     * Prisoners related to this one, for the grid at the bottom of the page.
     *
     * Relatedness is scored from what the records themselves share, in
     * decreasing order of meaning: the same affiliation (the same
     * organization), a case at the same institution (codefendants and fellow
     * inmates), the same era, a shared ideology, the same state. Weak
     * matches are dropped rather than padded — a record that shares only an
     * era with 2,000 others has no meaningful neighbors, and padding the
     * grid with near-strangers would dilute the pages where it is real (the
     * co-defendants of a mass trial, the members of one organization).
     *
     * How many survive is decided by relatedCount() and varies by page.
     * Candidate pools are bounded so a page render never scans the whole
     * table. Uses the default query scope, so under-review records never
     * surface here.
     */
    private function relatedPrisoners(Prisoner $prisoner)
    {
        $cols = ['id', 'name', 'slug', 'photo', 'era', 'state', 'affiliation', 'ideologies', 'sort_order'];
        $pool = collect();

        // Fellow inmates / codefendants: anyone with a case at one of this
        // prisoner's institutions.
        $instIds = $prisoner->cases->pluck('institution_id')->filter()->unique();
        $instMates = collect();
        if ($instIds->isNotEmpty()) {
            $instMates = PrisonerCase::whereIn('institution_id', $instIds)
                ->where('prisoner_id', '!=', $prisoner->id)
                ->limit(600)
                ->pluck('prisoner_id')
                ->unique();
            $pool = $pool->merge(Prisoner::whereIn('id', $instMates)->limit(250)->get($cols));
        }

        // Same organization. The affiliation column is a JSON array; matching
        // on the JSON-encoded value keeps the query portable across SQLite
        // and MySQL (no whereJsonContains), and the PHP scoring below
        // re-verifies the overlap so a LIKE false positive cannot rank.
        foreach ((array) $prisoner->affiliation as $aff) {
            if (! is_string($aff) || $aff === '') {
                continue;
            }
            $needle = str_replace(['%', '_'], ['\\%', '\\_'], json_encode($aff));
            $pool = $pool->merge(
                Prisoner::where('id', '!=', $prisoner->id)
                    ->where('affiliation', 'like', '%'.$needle.'%')
                    ->limit(150)
                    ->get($cols)
            );
        }

        // Filler pool: same era, photo first, so thin records still get
        // era-plus-something matches to consider.
        if ($prisoner->era) {
            $pool = $pool->merge(
                Prisoner::where('id', '!=', $prisoner->id)
                    ->where('era', $prisoner->era)
                    ->when($prisoner->state, fn ($q) => $q->where('state', $prisoner->state))
                    ->orderByRaw("(photo is null or photo = '') asc")
                    ->orderBy('sort_order')
                    ->limit(80)
                    ->get($cols)
            );
        }

        $myAff = collect((array) $prisoner->affiliation)->filter();
        $myIdeo = collect((array) $prisoner->ideologies)->filter();
        $instSet = $instMates->flip();

        return $pool->unique('id')
            ->map(function ($p) use ($prisoner, $myAff, $myIdeo, $instSet) {
                $score = 0;
                $score += 4 * $myAff->intersect((array) $p->affiliation)->count();
                $score += 2 * ($instSet->has($p->id) ? 1 : 0);
                $score += 2 * ($prisoner->era && $p->era === $prisoner->era ? 1 : 0);
                $score += min(2, $myIdeo->intersect((array) $p->ideologies)->count());
                $score += ($prisoner->state && $p->state === $prisoner->state) ? 1 : 0;
                $p->related_score = $score;

                return $p;
            })
            // One shared fact is not a relationship: era alone (2) or an
            // institution alone (2) does not clear the bar; era+state,
            // era+ideology, any shared affiliation, institution+era all do.
            ->filter(fn ($p) => $p->related_score >= 3)
            ->sortBy([
                ['related_score', 'desc'],
                fn ($a, $b) => (filled($b->photo) <=> filled($a->photo)),
                ['sort_order', 'asc'],
            ])
            ->pipe(fn ($ranked) => $ranked->take($this->relatedCount($ranked)))
            ->values();
    }

    public function home() {
        return view('home');
    }

    public function museum() {
        // The payload build below scans every prisoner with a photo, matches 16
        // group haystacks, and gathers timeline/archive/reading/shop data — far
        // too heavy to run per request. Content changes only on admin edits, so
        // the whole thing is cached (same tradeoff as tracker()); run
        // `php artisan cache:clear` to refresh immediately.
        $museum = Cache::remember('museum:payload:v2', now()->addHours(6), fn () => $this->computeMuseumPayload());

        return view('pages.museum', compact('museum'));
    }

    private function computeMuseumPayload(): array {
        // Walkable 3D museum (/museum). Curates prisoners who have photos into
        // named galleries by the specific movement/organization they belonged to
        // (the Black Panther Party, Plowshares, the American Indian Movement …),
        // rather than strewing portraits at random, and gathers the timeline,
        // archive documents, and topic backdrops the rooms are built from.
        // Fields are keyed short to keep the embedded payload small:
        // n=name, img=photo, l1/l2=placard lines, d=description, u=link.
        //
        // Each group matches a haystack of the prisoner's affiliation + ideology
        // tags. Groups are ordered as a curatorial sequence; a person lands in
        // the first group they match (dedup by $used), and a group only becomes a
        // room if it collects enough portraits.
        $groupDefs = [
            ['key' => 'black-panther', 'title' => 'The Black Panther Party', 'accent' => 'crimson',
                'intro' => 'Founded in Oakland in 1966, the Black Panther Party became the FBI\'s single largest COINTELPRO target. Its members were surveilled, raided, and framed; several — like the New York and New Haven Panthers — spent years in pretrial jail before juries acquitted them. Others never came home.',
                'match' => ['black panther', 'panther 21', 'panther', 'bpp']],
            ['key' => 'black-liberation-army', 'title' => 'The Black Liberation Army & New Afrika', 'accent' => 'gold',
                'intro' => 'Out of the Panthers\' collapse came the Black Liberation Army and the Republic of New Afrika. Their prisoners hold some of the longest sentences in this building — many jailed since the 1970s and 1980s, several still inside today, some of the last acknowledged political prisoners in the United States.',
                'match' => ['black liberation army', 'new afrika', 'new afrikan', 'republic of new', 'bla ', 'assata']],
            ['key' => 'move', 'title' => 'MOVE', 'accent' => 'green',
                'intro' => 'The MOVE organization, a Black naturalist commune in Philadelphia, was besieged twice by the city — in 1978 and again in 1985, when police dropped a bomb on their home and let the fire burn a city block. The MOVE 9 spent four decades in prison for a death the state itself set in motion.',
                'match' => ['move organization', 'move 9', 'move nine', 'move bombing', 'the move']],
            ['key' => 'aim', 'title' => 'The American Indian Movement', 'accent' => 'ochre',
                'intro' => 'From the occupation of Wounded Knee to the shootout at Pine Ridge, the American Indian Movement met federal conspiracy charges, paramilitary sieges, and prosecutions that reached across decades. Leonard Peltier\'s case became the movement\'s defining injustice.',
                'match' => ['american indian movement', 'wounded knee', 'pine ridge', 'peltier', 'indigenous', 'native american', 'aim ']],
            ['key' => 'puerto-rico', 'title' => 'Puerto Rican Independence', 'accent' => 'teal',
                'intro' => 'For more than a century the United States has jailed Puerto Ricans who insisted their island be free — the Nationalist Party of the 1950s, the FALN and Los Macheteros of the 1970s and 80s, prisoners held so long that mass clemency campaigns finally brought some of them home.',
                'match' => ['puerto ric', 'faln', 'macheteros', 'nationalist party', 'young lords', 'independentista', 'boricua']],
            ['key' => 'plowshares', 'title' => 'Plowshares & the Catholic Left', 'accent' => 'violet',
                'intro' => 'Priests, nuns, and lay Catholics who hammered on nuclear warheads and poured blood on missile silos — beating swords into plowshares, literally — have accepted years in federal prison as an act of faith. Many were in their seventies and eighties when they were sentenced.',
                'match' => ['plowshares', 'catholic worker', 'berrigan', 'king of prussia', 'disarmament', 'anti-nuclear', 'ploughshares']],
            ['key' => 'antiwar', 'title' => 'Draft Resistance & the Anti-War Movement', 'accent' => 'slate',
                'intro' => 'From the conscientious objectors of the First World War to the draft-card burners of Vietnam, refusing to fight has carried a prison sentence. This gallery holds those who went to jail rather than to war.',
                'match' => ['draft', 'vietnam', 'conscientious objector', 'anti-war', 'antiwar', 'war resist', 'selective service', 'pacifis']],
            ['key' => 'weather', 'title' => 'Weather Underground & the Anti-Imperialists', 'accent' => 'rust',
                'intro' => 'The white radicals who broke from the student movement to fight alongside Black and Puerto Rican liberation — the Weather Underground, the May 19th Communist Organization, the Ohio 7 — drew long federal sentences under conspiracy and RICO statutes.',
                'match' => ['weather underground', 'weathermen', 'may 19', 'may 19th', 'anti-imperialist', 'ohio 7', 'united freedom front', 'sds', 'students for a democratic']],
            ['key' => 'anarchists', 'title' => 'The Anarchists', 'accent' => 'crimson',
                'intro' => 'From the Haymarket martyrs hanged in 1887 to the Galleanists deported after the Red Scare, anarchists were the first political prisoners this country made in great numbers — jailed for their newspapers, their speeches, and their refusal of the state itself.',
                'match' => ['anarch', 'haymarket', 'galleanist', 'galleani', 'nihilis']],
            ['key' => 'labor', 'title' => 'Labor & the Industrial Workers of the World', 'accent' => 'ochre',
                'intro' => 'Union organizers filled American prisons under criminal-syndicalism laws written expressly to break them. The Industrial Workers of the World — the Wobblies — were jailed by the hundreds; the mine and pecan and farm strikes of the 1930s filled the cells again.',
                'match' => ['iww', 'industrial workers', 'wobbl', 'criminal syndicalis', 'labor', 'trade union', 'longshore', 'pecan', 'miner']],
            ['key' => 'communists', 'title' => 'Communists & the Red Scare', 'accent' => 'crimson',
                'intro' => 'Under the Smith Act and the subpoenas of the House Un-American Activities Committee, holding the wrong ideas — or refusing to name those who did — was enough for prison. The Hollywood Ten and the Party leadership went to jail for their beliefs and their silence.',
                'match' => ['communist', 'smith act', 'hollywood ten', 'huac', 'un-american', 'red scare', 'cpusa', 'party leader']],
            ['key' => 'earth', 'title' => 'The Green Scare', 'accent' => 'green',
                'intro' => 'In the 2000s the government branded environmental and animal-rights saboteurs as terrorists, winning some of the harshest sentences ever handed to activists who took no life — the Earth and Animal Liberation Fronts, the SHAC defendants, the eco-arsonists of Operation Backfire.',
                'match' => ['earth liberation', 'animal liberation', 'green scare', 'ecodefense', 'shac', 'operation backfire', 'environmental', ' elf', ' alf']],
            ['key' => 'civil-rights', 'title' => 'The Civil Rights Movement', 'accent' => 'gold',
                'intro' => 'The Freedom Riders who filled the jails of Mississippi, the students of SNCC and CORE, the marchers who went to prison by the thousands — the movement made a strategy of arrest, turning the cell into a place of witness.',
                'match' => ['civil rights', 'sncc', ' core', 'freedom rider', 'freedom ride', 'naacp', 'sclc', 'birmingham', 'montgomery']],
            ['key' => 'suffrage', 'title' => 'Suffrage & Women\'s Rights', 'accent' => 'violet',
                'intro' => 'The Silent Sentinels were dragged from the White House gates to the Occoquan Workhouse and force-fed for demanding the vote. Birth-control advocates chose jail over silence. This gallery holds the women — and their allies — imprisoned for equality.',
                'match' => ['suffrag', 'silent sentinel', "woman's party", 'birth control', 'feminis']],
            ['key' => 'palestine', 'title' => 'Palestine Solidarity', 'accent' => 'teal',
                'intro' => 'The newest wing of this collection: organizers, students, and writers detained, deported, or prosecuted for pro-Palestinian speech and protest — a reminder that the making of political prisoners in America is not a closed chapter but a present one.',
                'match' => ['palestin', 'gaza', 'pro-palestine', 'boycott', 'bds', 'holy land']],
            ['key' => 'grand-jury', 'title' => 'Grand Jury & Contempt Resisters', 'accent' => 'slate',
                'intro' => 'People jailed not for a crime but for a refusal — declining to testify before a grand jury, to name names, or to become an instrument against their own movement. Their sentences are open-ended: they end only when the resister breaks, or the jury does.',
                'match' => ['grand jury', 'contempt', 'refused to testify', 'refusing to testify', 'noncooperat']],
        ];

        $people = Prisoner::whereNotNull('photo')->where('photo', '!=', '')
            ->get(['id', 'name', 'slug', 'photo', 'description', 'era', 'state', 'ideologies', 'affiliation', 'birthdate', 'death_date', 'in_custody', 'date_precision']);

        $item = function ($p) {
            $born = $p->birthdate ? $p->birthdate->format('Y') : null;
            $died = $p->death_date ? $p->death_date->format('Y') : null;
            $years = $born ? ($born.'–'.($died ?: ($p->in_custody ? '' : ' '))) : ($died ? '–'.$died : '');
            $ideo = collect((array) $p->ideologies)->take(2)->implode(' · ');

            return [
                'n' => $p->name,
                'img' => $p->photo_url,
                'l1' => trim($years) ?: ($p->era ?: ''),
                'l2' => $ideo ?: ($p->era ?: ''),
                'd' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $p->description)), 620),
                'u' => '/prisoner/'.$p->slug,
                'c' => (bool) $p->in_custody,
            ];
        };
        $hayOf = fn ($p) => ' '.mb_strtolower(implode(' ', array_merge(
            (array) $p->affiliation, (array) $p->ideologies, [(string) $p->name, (string) $p->era]
        ))).' ';

        $used = [];
        $galleries = [];
        foreach ($groupDefs as $t) {
            $picks = [];
            foreach ($people as $p) {
                if (isset($used[$p->id])) {
                    continue;
                }
                $hay = $hayOf($p);
                foreach ($t['match'] as $m) {
                    if (str_contains($hay, $m)) {
                        $picks[] = $p;
                        break;
                    }
                }
            }
            // Richest bios first make the best wall labels; big rooms hold ~16.
            $picks = collect($picks)->sortByDesc(fn ($p) => mb_strlen((string) $p->description))->take(16)->values();
            if ($picks->count() >= 3) {
                foreach ($picks as $p) {
                    $used[$p->id] = true;
                }
                $galleries[] = [
                    'key' => $t['key'],
                    'title' => $t['title'],
                    'intro' => $t['intro'],
                    'accent' => $t['accent'],
                    'items' => $picks->map($item)->all(),
                ];
            }
        }

        // Hall of Figures — a curated set of standing portrait monoliths: the
        // single strongest-documented figure from each populated gallery, so the
        // central hall reads as a who's-who across the movements.
        $monoliths = [];
        foreach ($galleries as $g) {
            if (! empty($g['items'][0])) {
                $monoliths[] = $g['items'][0] + ['group' => $g['title']];
            }
        }

        // Face corridors + memorial ring: museum.js consumes fixed slices up to
        // index 21 plus a modulo-cycled ring, so anything past ~24 entries is
        // dead payload weight (embedding all ~1,300 leftover portraits was ~900KB
        // of the inline JSON). The dense mosaic walls use $mosaic below instead.
        $faces = collect($people)->reject(fn ($p) => isset($used[$p->id]))
            ->sortByDesc(fn ($p) => mb_strlen((string) $p->description))
            ->take(24)->values()->map($item)->all();
        $mosaic = collect($people)
            ->sortByDesc(fn ($p) => mb_strlen((string) $p->description))
            ->take(180)->values()
            ->map(fn ($p) => ['img' => $p->photo_url, 'n' => $p->name, 'u' => '/prisoner/'.$p->slug])->all();

        // Rotunda standees: figures still in custody get pride of place.
        $standees = collect($people)->filter(fn ($p) => $p->in_custody && mb_strlen((string) $p->description) > 200)
            ->sortByDesc(fn ($p) => mb_strlen((string) $p->description))
            ->take(6)->values()->map($item)->all();

        $timeline = Timeline::orderBy('year')->get(['year', 'title', 'text', 'image'])
            ->map(fn ($t) => [
                'y' => (int) $t->year,
                't' => $t->title,
                'x' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $t->text)), 260),
                'img' => $t->image ? \Illuminate\Support\Facades\Storage::url($t->image) : null,
            ])->all();

        $archive = ArchiveRecord::where('published', true)->whereNotNull('thumbnail')
            ->orderBy('year')->take(10)
            ->get()
            ->map(fn ($r) => [
                'n' => $r->title,
                'img' => $r->thumbnail_url,
                'file' => $r->file_url,
                'l1' => trim(($r->year ?: '').' · '.($r->source_format ?: $r->record_type)),
                'l2' => $r->collection ?: '',
                'd' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $r->description)), 420),
                'u' => '/archive/view/'.$r->slug,
            ])->all();

        // Reading room: every digitized document, split into "books" (long-form
        // — zines, periodicals, pamphlets — shelved on the bookcases) and
        // "sheets" (flyers, posters — racked face-out). Picking one up opens
        // the in-museum PDF reader on the full scan.
        $shortFormats = ['flyer', 'poster', 'broadside', 'photograph', 'postcard', 'card'];
        $reading = ArchiveRecord::where('published', true)->whereNotNull('file')
            ->orderBy('collection')->orderBy('year')
            ->take(56)
            ->get()
            ->map(fn ($r) => [
                'n' => $r->title,
                'img' => $r->thumbnail_url,
                'file' => $r->file_url,
                'l1' => trim(($r->year ?: '').' · '.($r->source_format ?: $r->record_type), ' ·'),
                'l2' => $r->collection ?: '',
                'd' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $r->description)), 300),
                'u' => '/archive/view/'.$r->slug,
                'book' => ! in_array(mb_strtolower((string) $r->source_format), $shortFormats, true),
            ])->all();

        // Projection / theater slides: the wide topic backdrops.
        $slides = Topic::published()->whereNotNull('image')->where('image', '!=', '')
            ->orderBy('sort_order')->take(14)
            ->get(['title', 'image'])
            ->map(fn ($t) => [
                't' => $t->title,
                'img' => \Illuminate\Support\Facades\Storage::url($t->image),
            ])->all();

        $stats = [
            'total' => Prisoner::count(),
            'inCustody' => Prisoner::where('in_custody', true)->count(),
            'eras' => (int) Prisoner::whereNotNull('era')->where('era', '!=', '')->distinct()->count('era'),
        ];

        // Museum shop: real store products, classified into a display type so
        // the 3D shop knows where each belongs (apparel rail, bookshelf,
        // poster wall, sticker counter, misc shelf). Picking one up offers a
        // Buy action that opens the product's store page.
        $typeOf = function ($p) {
            $hay = mb_strtolower($p->name.' '.$p->category.' '.implode(' ', (array) ($p->categories ?? [])));
            $has = fn (...$words) => collect($words)->contains(fn ($w) => str_contains($hay, $w));

            return match (true) {
                $has('shirt', 'tee', 'hoodie', 'sweat', 'apparel', 'tote', 'bag', 'hat', 'cap', 'beanie') => 'apparel',
                $has('book', 'zine', 'pamphlet', 'reader', 'journal') => 'book',
                $has('sticker', 'pin', 'button', 'badge', 'patch', 'magnet', 'postcard', 'keychain') => 'small',
                $has('poster', 'print', 'flag', 'banner') => 'poster',
                default => 'misc',
            };
        };
        $shop = Product::published()->whereNotNull('image')->where('image', '!=', '')
            ->orderByDesc('featured')->orderBy('sort_order')->orderBy('name')
            ->take(26)->get()
            ->map(fn ($p) => [
                'n' => $p->name,
                'img' => $p->image_url,
                'l1' => '$'.number_format((float) $p->price, fmod((float) $p->price, 1.0) > 0.004 ? 2 : 0),
                'l2' => $p->category ?: 'Museum Shop',
                'd' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $p->description)), 300),
                'u' => '/store/'.$p->slug,
                'type' => $typeOf($p),
            ])->all();

        $museum = [
            'galleries' => $galleries,
            'monoliths' => $monoliths,
            'mosaic' => $mosaic,
            'faces' => $faces,
            'standees' => $standees,
            'timeline' => $timeline,
            'archive' => $archive,
            'reading' => $reading,
            'slides' => $slides,
            'shop' => $shop,
            'stats' => $stats,
            'video' => is_file(public_path('videos/museum-reel.mp4')) ? '/videos/museum-reel.mp4' : null,
        ];

        return $museum;
    }

    /**
     * Downscaled variants of public-disk images for the museum's progressive
     * art loader: /thumb/{w}/{path}. Widths are whitelisted; results are
     * written to storage/app/public/thumbs so repeat requests are cheap. Falls
     * back to redirecting to the original whenever GD or the decode fails, so
     * a bad image can never break the museum.
     */
    public function imageThumb(int $w, string $path) {
        if (! in_array($w, [64, 512, 1024], true)) {
            abort(404);
        }
        $path = str_replace('\\', '/', $path);
        if (str_contains($path, '..') || ! preg_match('#^[A-Za-z0-9_/\.\-]+\.(jpe?g|png|webp)$#i', $path)) {
            abort(404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $thumbRel = 'thumbs/'.$w.'/'.sha1($path).'.jpg';
        $thumbAbs = $disk->path($thumbRel);
        if (! is_file($thumbAbs)) {
            try {
                if (! function_exists('imagecreatefromstring')) {
                    throw new \RuntimeException('GD unavailable');
                }
                $src = imagecreatefromstring($disk->get($path));
                if (! $src) {
                    throw new \RuntimeException('decode failed');
                }
                $sw = imagesx($src);
                $sh = imagesy($src);
                if ($sw > $w) {
                    $dst = imagescale($src, $w, (int) round($sh * $w / $sw), IMG_BICUBIC);
                    imagedestroy($src);
                } else {
                    $dst = $src;   // already small enough — just re-encode
                }
                @mkdir(dirname($thumbAbs), 0775, true);
                imagejpeg($dst, $thumbAbs, $w <= 64 ? 70 : 82);
                imagedestroy($dst);
            } catch (\Throwable $e) {
                return redirect($disk->url($path));
            }
        }

        return response()->file($thumbAbs, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
