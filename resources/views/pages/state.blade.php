@extends('app')

@section('title', $name . ' — Political Prisoners by State | NPPC')

@section('meta_description')Documented political prisoners and cases in {{ $name }}: {{ $stats['total'] }} entries in the NPPC census, with institutions, eras, and case histories.@endsection

@section('head')
<style>
/* State directory page (linked from the Learn More state-by-state grid). */
.stp { --acc: #5660fe; --acc2: #8f97ff; color: var(--fg); }
.stp a { text-decoration: none; }

/* hero */
.stp-hero { position: relative; padding: 90px 24px 70px; overflow: hidden; border-bottom: 1px solid rgba(var(--fg-rgb),.1); }
.stp-hero-in { max-width: 1180px; margin: 0 auto; position: relative; }
.stp-shape { position: absolute; right: -30px; top: 50%; transform: translateY(-50%); width: min(420px, 40vw); opacity: .07; pointer-events: none; }
.stp-shape svg { width: 100%; height: auto; display: block; color: var(--fg); }
.stp-crumb { font-size: 12px; letter-spacing: .18em; text-transform: uppercase; color: rgba(var(--fg-rgb),.5); margin-bottom: 18px; }
.stp-crumb a { color: var(--acc2); }
.stp-hero h1 { font-size: clamp(2.6rem, 6vw, 4.6rem); font-weight: 900; letter-spacing: -.02em; line-height: 1.05; margin: 0 0 18px; color: var(--fg); }
.stp-hero .lede { max-width: 640px; color: rgba(var(--fg-rgb),.7); font-size: 16.5px; line-height: 1.7; }
.stp-stats { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 30px; }
.stp-stat { border: 1px solid rgba(var(--fg-rgb),.14); border-radius: 8px; padding: 14px 20px; min-width: 130px; }
.stp-stat b { display: block; font-size: 1.7rem; font-weight: 900; color: var(--acc2); line-height: 1.1; }
.stp-stat span { font-size: 12px; letter-spacing: .1em; text-transform: uppercase; color: rgba(var(--fg-rgb),.55); }

.stp-section { max-width: 1180px; margin: 0 auto; padding: 60px 24px 20px; }
.stp-h2 { font-size: clamp(1.4rem, 2.6vw, 2rem); font-weight: 900; color: var(--fg); margin: 0 0 8px; }
.stp-sub { font-size: 14px; color: rgba(var(--fg-rgb),.55); margin: 0 0 30px; }

/* recent dashboard entries */
.stp-news { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.stp-news-card { border: 1px solid rgba(var(--fg-rgb),.12); border-radius: 9px; padding: 19px 20px; background: rgba(var(--fg-rgb),.02); color: var(--fg); transition: border-color .2s, transform .2s; }
.stp-news-card:hover { border-color: rgba(86,96,254,.5); transform: translateY(-2px); }
.stp-news-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.stp-news-cat { font-size: 10px; line-height: 1; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; padding: 5px 8px; border-radius: 3px; background: rgba(86,96,254,.14); color: var(--acc2); }
.stp-news-cat.cat-arrest { background: rgba(239,68,68,.14); color: #ef4444; }
.stp-news-cat.cat-prosecution { background: rgba(163,113,247,.14); color: #a371f7; }
.stp-news-cat.cat-protest { background: rgba(25,195,125,.14); color: #19c37d; }
.stp-news-date { color: rgba(var(--fg-rgb),.48); font-size: 11.5px; white-space: nowrap; }
.stp-news-title { margin: 0; color: var(--fg); font-size: 15px; font-weight: 820; line-height: 1.4; }
.stp-news-meta { margin-top: 12px; color: rgba(var(--fg-rgb),.5); font-size: 11.5px; line-height: 1.45; }
.stp-news-more { display: inline-flex; margin-top: 22px; color: var(--acc2); font-size: 13px; font-weight: 800; }
.stp-news-more:hover { text-decoration: underline; }
@media (max-width: 760px) { .stp-news { grid-template-columns: 1fr; } }

/* era chips */
.stp-eras { display: flex; gap: 10px; flex-wrap: wrap; margin: 0 0 10px; }
.stp-era { font-size: 13px; font-weight: 700; color: rgba(var(--fg-rgb),.8); border: 1px solid rgba(var(--fg-rgb),.16); border-radius: 999px; padding: 7px 16px; }
.stp-era b { color: var(--acc2); font-weight: 900; }

/* prisoner grid */
.stp-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
.stp-card { border: 1px solid rgba(var(--fg-rgb),.1); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: border-color .2s, transform .2s; background: rgba(var(--fg-rgb),.02); }
.stp-card:hover { border-color: rgba(86,96,254,.45); transform: translateY(-3px); }
.stp-photo { aspect-ratio: 4 / 4.4; background: center top / cover no-repeat; }
.stp-photo.is-empty { display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, rgba(86,96,254,.16), rgba(86,96,254,.04)); }
.stp-photo.is-empty b { font-size: 3.4rem; font-weight: 900; color: rgba(143,151,255,.5); }
.stp-card-body { padding: 16px 16px 18px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.stp-name { font-size: 15.5px; font-weight: 800; color: var(--fg); line-height: 1.3; }
.stp-badge { align-self: flex-start; font-size: 10.5px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; border-radius: 3px; padding: 3px 8px; }
.stp-badge.b-custody { background: rgba(239,68,68,.14); color: #ef4444; border: 1px solid rgba(239,68,68,.3); }
.stp-badge.b-released { background: rgba(34,197,94,.14); color: #22c55e; border: 1px solid rgba(34,197,94,.3); }
.stp-badge.b-awaiting { background: rgba(234,179,8,.14); color: #eab308; border: 1px solid rgba(234,179,8,.3); }
.stp-badge.b-exile { background: rgba(143,151,255,.14); color: var(--acc2); border: 1px solid rgba(143,151,255,.3); }
.stp-desc { font-size: 12.5px; color: rgba(var(--fg-rgb),.55); line-height: 1.6; }
@media (max-width: 1020px) { .stp-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 760px)  { .stp-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 460px)  { .stp-grid { grid-template-columns: 1fr; } }

/* pagination (matches the news pager idiom) */
.stp-pager { margin: 40px 0 10px; display: flex; justify-content: center; }
.stp-pager .pagination { display: flex; gap: 6px; list-style: none; padding: 0; margin: 0; flex-wrap: wrap; }
.stp-pager .pagination li a, .stp-pager .pagination li span { display: inline-block; min-width: 38px; text-align: center; padding: 9px 12px; border: 1px solid rgba(var(--fg-rgb),.15); border-radius: 6px; color: var(--fg); font-size: 14px; font-weight: 700; }
.stp-pager .pagination li.active span { background: var(--acc); border-color: var(--acc); color: #fff; }
.stp-pager .pagination li.disabled span { opacity: .35; }
.stp-pager .pagination li a:hover { border-color: var(--acc); }

/* institutions */
.stp-insts { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.stp-inst { border: 1px solid rgba(var(--fg-rgb),.1); border-radius: 8px; padding: 18px 20px; display: flex; justify-content: space-between; align-items: baseline; gap: 16px; }
.stp-inst b { font-size: 15px; font-weight: 800; color: var(--fg); }
.stp-inst small { display: block; font-size: 12.5px; color: rgba(var(--fg-rgb),.5); margin-top: 3px; }
.stp-inst .n { font-size: 13px; font-weight: 900; color: var(--acc2); white-space: nowrap; }
@media (max-width: 760px) { .stp-insts { grid-template-columns: 1fr; } }

/* empty state */
.stp-empty { border: 1px dashed rgba(var(--fg-rgb),.2); border-radius: 10px; padding: 50px 30px; text-align: center; color: rgba(var(--fg-rgb),.6); }
.stp-empty b { display: block; font-size: 1.2rem; color: var(--fg); margin-bottom: 8px; }

/* footer nav */
.stp-nav { max-width: 1180px; margin: 0 auto; padding: 50px 24px 90px; display: flex; justify-content: space-between; gap: 20px; }
.stp-nav a { font-size: 14.5px; font-weight: 800; color: var(--acc2); }
.stp-nav a:hover { text-decoration: underline; }
</style>
@endsection

@section('body')
<div class="stp">

    <div class="stp-hero">
        <div class="stp-hero-in">
            @if ($shape)
                <div class="stp-shape" aria-hidden="true">
                    <svg viewBox="{{ $shape['viewBox'] }}" preserveAspectRatio="xMidYMid meet"><path d="{{ $shape['path'] }}" fill="currentColor"/></svg>
                </div>
            @endif
            <div class="stp-crumb"><a href="/learn-more">Learn More</a> &rarr; State by State</div>
            <h1>{{ $name }}</h1>
            <p class="lede">Political repression plays out state by state &mdash; shaped by local institutions,
            prosecutors, and movements for defense. These are the documented political prisoners and cases the
            census holds for {{ $name }}.</p>
            <div class="stp-stats">
                <div class="stp-stat"><b>{{ number_format($stats['total']) }}</b><span>Documented</span></div>
                <div class="stp-stat"><b>{{ number_format($stats['in_custody']) }}</b><span>In custody</span></div>
                <div class="stp-stat"><b>{{ number_format($stats['released']) }}</b><span>Released</span></div>
                @if ($stats['awaiting'])
                    <div class="stp-stat"><b>{{ number_format($stats['awaiting']) }}</b><span>Awaiting trial</span></div>
                @endif
            </div>
        </div>
    </div>

    @if ($eras->isNotEmpty())
        <div class="stp-section" style="padding-bottom: 0;">
            <h2 class="stp-h2">Eras of repression in {{ $name }}</h2>
            <p class="stp-sub">Where {{ $name }}&rsquo;s entries fall across the census&rsquo;s historical eras.</p>
            <div class="stp-eras">
                @foreach ($eras as $era)
                    <span class="stp-era">{{ $era->era }} <b>{{ $era->n }}</b></span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($dashboardEntries->isNotEmpty())
        <div class="stp-section">
            <h2 class="stp-h2">Recent dashboard entries from {{ $name }}</h2>
            <p class="stp-sub">The newest published events and reporting located in {{ $name }}, updated automatically from the live dashboard.</p>
            <div class="stp-news">
                @foreach ($dashboardEntries as $entry)
                    <a class="stp-news-card" href="{{ $entry->url }}" @if(str_starts_with($entry->url, 'http')) target="_blank" rel="noopener" @endif>
                        <div class="stp-news-top">
                            <span class="stp-news-cat cat-{{ $entry->category ?: 'other' }}">{{ \App\Models\DashboardLink::CATEGORIES[$entry->category] ?? 'Other' }}</span>
                            <time class="stp-news-date" datetime="{{ $entry->published_at->toDateString() }}">{{ $entry->published_at->format('M j, Y') }}</time>
                        </div>
                        <h3 class="stp-news-title">{{ $entry->title }}</h3>
                        <div class="stp-news-meta">{{ collect([$entry->location_label, $entry->source])->filter()->join(' · ') }}</div>
                    </a>
                @endforeach
            </div>
            <a class="stp-news-more" href="/dashboard">Open the live dashboard &rarr;</a>
        </div>
    @endif

    <div class="stp-section">
        <h2 class="stp-h2">Documented cases</h2>
        <p class="stp-sub">
            {{ number_format($stats['total']) }} {{ $stats['total'] === 1 ? 'entry' : 'entries' }} in the census
            &mdash; every one stays in the record, whether the case is open or closed.
        </p>

        @if ($prisoners->isEmpty())
            <div class="stp-empty">
                <b>No documented cases in {{ $name }} yet.</b>
                The census grows every week as volunteers and defense committees submit cases.
                Know of one? <a href="/contact" style="color: var(--acc2);">Tell the intake team.</a>
            </div>
        @else
            <div class="stp-grid">
                @foreach ($prisoners as $p)
                    <a class="stp-card" href="/prisoner/{{ $p->slug ?? $p->id }}">
                        @if ($p->photo)
                            <div class="stp-photo" style="background-image: url('{{ $p->photo_url }}')"></div>
                        @else
                            <div class="stp-photo is-empty"><b>{{ mb_substr($p->name, 0, 1) }}</b></div>
                        @endif
                        <div class="stp-card-body">
                            <div class="stp-name">{{ $p->name }}</div>
                            @if ($p->in_custody)
                                <span class="stp-badge b-custody">In Custody</span>
                            @elseif ($p->awaiting_trial)
                                <span class="stp-badge b-awaiting">Awaiting Trial</span>
                            @elseif ($p->released)
                                <span class="stp-badge b-released">Released</span>
                            @elseif ($p->in_exile)
                                <span class="stp-badge b-exile">In Exile</span>
                            @endif
                            @if ($p->description)
                                <div class="stp-desc">{{ \Illuminate\Support\Str::limit(strip_tags($p->description), 110) }}</div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="stp-pager">{{ $prisoners->links('vendor.pagination.nppc') }}</div>
        @endif
    </div>

    @if ($institutions->isNotEmpty())
        <div class="stp-section">
            <h2 class="stp-h2">Institutions in {{ $name }}</h2>
            <p class="stp-sub">Prisons, jails, and detention centers in {{ $name }} that appear in the census&rsquo;s case files.</p>
            <div class="stp-insts">
                @foreach ($institutions as $inst)
                    <div class="stp-inst">
                        <div><b>{{ $inst->name }}</b>@if ($inst->city)<small>{{ $inst->city }}, {{ $name }}</small>@endif</div>
                        <span class="n">{{ $inst->cases_count }} {{ $inst->cases_count === 1 ? 'case' : 'cases' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="stp-nav">
        <a href="/state/{{ $prevState['slug'] }}">&larr; {{ $prevState['name'] }}</a>
        <a href="/learn-more">All states</a>
        <a href="/state/{{ $nextState['slug'] }}">{{ $nextState['name'] }} &rarr;</a>
    </div>

</div>
@endsection
