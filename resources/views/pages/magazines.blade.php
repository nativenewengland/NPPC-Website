@php
    $hasFilters = $q !== '' || $collection !== '' || ($year !== null && $year !== '') || $availableOnly;
    $recordLabel = function ($record) {
        $format = strtolower((string) ($record->source_format ?: $record->record_type));
        return match ($format) {
            'newsletter' => 'Newsletter',
            'newspaper' => 'Newspaper',
            'zine' => 'Zine',
            'journal' => 'Journal',
            default => 'Magazine',
        };
    };
@endphp

@extends('app')

@section('title', 'Magazines & Movement Press | NPPC')

@section('body')
<main class="mg-page">
    <section class="mg-hero" aria-labelledby="magazines-title">
        <div class="mg-covers" aria-hidden="true">
            @foreach ($heroThumbs as $thumb)
                <span style="background-image:url('{{ $thumb }}')"></span>
            @endforeach
        </div>
        <div class="mg-shade"></div>
        <div class="mg-hero-copy">
            <p class="mg-kicker">NPPC Digital Archive</p>
            <h1 id="magazines-title">Magazines &amp;<br>Movement Press</h1>
            <p>Read the periodicals that documented political imprisonment, resistance, solidarity, and liberation movements across generations.</p>
        </div>
    </section>

    <section class="mg-stats" aria-label="Collection statistics">
        <div><strong>{{ number_format($total) }}</strong><span>periodical records</span></div>
        <div><strong>{{ number_format($digitized) }}</strong><span>available to read</span></div>
        <div><strong>{{ number_format($scanNeeded) }}</strong><span>issues awaiting scans</span></div>
    </section>

    <section class="mg-intro">
        <p>This catalog is drawn directly from the NPPC Archive, so newly preserved issues appear here automatically. It includes complete and partial runs of newspapers, newsletters, journals, magazines, and movement zines. Records marked <strong>scan wanted</strong> identify known issues that have not yet been digitized.</p>
        <a href="/archive?source_format=periodical&include_nondigitized=1">Search the full Archive <span aria-hidden="true">→</span></a>
    </section>

    @if ($featuredCollections->isNotEmpty())
        <section class="mg-featured" aria-labelledby="featured-runs">
            <div class="mg-heading">
                <p>Browse by publication</p>
                <h2 id="featured-runs">Featured runs</h2>
            </div>
            <div class="mg-run-grid">
                @foreach ($featuredCollections as $featured)
                    <a href="/magazines?{{ http_build_query(['collection' => $featured['name']]) }}">
                        <span>{{ $featured['name'] }}</span>
                        <b>{{ number_format($featured['count']) }} {{ $featured['count'] === 1 ? 'issue' : 'issues' }}</b>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mg-browser" aria-labelledby="browse-periodicals">
        <div class="mg-heading">
            <p>Search the shelves</p>
            <h2 id="browse-periodicals">All periodicals</h2>
        </div>

        <form class="mg-filters" action="/magazines" method="get">
            <label class="mg-search">
                <span>Search</span>
                <input type="search" name="q" value="{{ $q }}" placeholder="Title, publisher, topic…">
            </label>
            <label>
                <span>Publication</span>
                <select name="collection">
                    <option value="">All publications</option>
                    @foreach ($collectionCounts as $name => $count)
                        <option value="{{ $name }}" @selected($collection === $name)>{{ $name }} ({{ $count }})</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Year</span>
                <select name="year">
                    <option value="">All years</option>
                    @foreach ($years as $optionYear)
                        <option value="{{ $optionYear }}" @selected((string) $year === (string) $optionYear)>{{ $optionYear }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Sort</span>
                <select name="sort">
                    <option value="newest" @selected($sort === 'newest')>Newest first</option>
                    <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
                    <option value="title" @selected($sort === 'title')>Title A–Z</option>
                </select>
            </label>
            <label class="mg-check">
                <input type="checkbox" name="available_only" value="1" @checked($availableOnly)>
                <span>Available to read</span>
            </label>
            <button type="submit">Apply filters</button>
            @if ($hasFilters)
                <a class="mg-clear" href="/magazines">Clear</a>
            @endif
        </form>

        <div class="mg-results-line">
            <p><strong>{{ number_format($records->total()) }}</strong> {{ $records->total() === 1 ? 'record' : 'records' }}</p>
            @if ($collection !== '')<span>{{ $collection }}</span>@endif
        </div>

        @if ($records->isEmpty())
            <div class="mg-empty">
                <h3>No periodicals matched those filters.</h3>
                <p>Try a broader search or <a href="/magazines">clear the filters</a>.</p>
            </div>
        @else
            <div class="mg-grid">
                @foreach ($records as $record)
                    <article class="mg-card">
                        <div class="mg-cover">
                            @if ($record->thumbnail_url)
                                <img src="{{ $record->thumbnail_url }}" alt="Cover of {{ $record->title }}" loading="lazy">
                            @else
                                <div class="mg-fallback" aria-hidden="true">
                                    <span>{{ $recordLabel($record) }}</span>
                                    <b>{{ $record->year ?: 'NPPC' }}</b>
                                    <i>Movement<br>Press</i>
                                </div>
                            @endif
                            @if (! $record->is_digitized || ! $record->file_url)
                                <span class="mg-scan-badge">Scan wanted</span>
                            @endif
                        </div>
                        <div class="mg-card-copy">
                            <p class="mg-meta">
                                <span>{{ $recordLabel($record) }}</span>
                                @if ($record->date)
                                    <time datetime="{{ $record->date->format('Y-m-d') }}">{{ $record->date->format('M j, Y') }}</time>
                                @elseif ($record->year)
                                    <time>{{ $record->year }}</time>
                                @endif
                            </p>
                            <h3>{{ $record->title }}</h3>
                            @if ($record->collection || $record->volume)
                                <p class="mg-series">{{ $record->collection }}@if ($record->collection && $record->volume) · @endif{{ $record->volume }}</p>
                            @endif
                            @if ($record->description)
                                <p class="mg-description">{{ \Illuminate\Support\Str::limit(strip_tags($record->description), 155) }}</p>
                            @endif
                            @if ($record->is_digitized && $record->file_url)
                                <a class="mg-read" href="{{ $record->file_url }}" target="_blank" rel="noopener">Read issue <span aria-hidden="true">↗</span></a>
                            @else
                                <span class="mg-unavailable">Known issue · scan not yet available</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($records->hasPages())
                <nav class="mg-pagination" aria-label="Periodical pages">
                    @if ($records->previousPageUrl())
                        <a href="{{ $records->previousPageUrl() }}">← Previous</a>
                    @else
                        <span>← Previous</span>
                    @endif
                    <b>Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</b>
                    @if ($records->nextPageUrl())
                        <a href="{{ $records->nextPageUrl() }}">Next →</a>
                    @else
                        <span>Next →</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
</main>

<style>
    .mg-page { --mg-ink: var(--fg); --mg-muted: rgba(var(--fg-rgb),.68); --mg-line: rgba(var(--fg-rgb),.14); padding: 28px 0 70px; }
    .mg-hero { position:relative; min-height:430px; overflow:hidden; border:1px solid var(--mg-line); background:#17131f; display:flex; align-items:flex-end; }
    .mg-covers { position:absolute; inset:-8%; display:grid; grid-template-columns:repeat(6,1fr); transform:rotate(-3deg) scale(1.08); gap:8px; opacity:.62; }
    .mg-covers span { min-height:210px; background-size:cover; background-position:center top; filter:saturate(.65) contrast(1.08); }
    .mg-shade { position:absolute; inset:0; background:linear-gradient(90deg,rgba(9,7,14,.98) 0%,rgba(9,7,14,.82) 45%,rgba(9,7,14,.25) 100%),linear-gradient(0deg,rgba(9,7,14,.65),transparent 60%); }
    .mg-hero-copy { position:relative; z-index:1; max-width:670px; padding:54px; color:#fff; }
    .mg-kicker,.mg-heading>p { margin:0 0 8px; color:#efc34f; font-size:.78rem; font-weight:850; letter-spacing:.16em; text-transform:uppercase; }
    .mg-hero h1 { margin:0; font-size:clamp(3rem,7vw,6.6rem); line-height:.86; letter-spacing:-.055em; color:#fff; }
    .mg-hero-copy>p:last-child { max-width:620px; margin:26px 0 0; font-size:1.08rem; line-height:1.65; color:rgba(255,255,255,.82); }
    .mg-stats { display:grid; grid-template-columns:repeat(3,1fr); border:1px solid var(--mg-line); border-top:0; }
    .mg-stats div { padding:23px 28px; display:flex; align-items:baseline; gap:11px; border-right:1px solid var(--mg-line); }
    .mg-stats div:last-child { border-right:0; }
    .mg-stats strong { font-size:1.65rem; }
    .mg-stats span { color:var(--mg-muted); font-size:.83rem; text-transform:uppercase; letter-spacing:.06em; }
    .mg-intro { margin:38px 0 70px; padding:26px 30px; border-left:4px solid #efc34f; background:rgba(var(--fg-rgb),.035); display:flex; justify-content:space-between; align-items:center; gap:32px; }
    .mg-intro p { max-width:830px; margin:0; line-height:1.72; color:var(--mg-muted); }
    .mg-intro a { flex:0 0 auto; color:var(--mg-ink); font-weight:800; }
    .mg-heading { margin-bottom:22px; }
    .mg-heading h2 { margin:0; font-size:clamp(2rem,4vw,3.5rem); letter-spacing:-.04em; line-height:1; }
    .mg-featured { margin-bottom:78px; }
    .mg-run-grid { display:grid; grid-template-columns:repeat(4,1fr); border-top:1px solid var(--mg-line); border-left:1px solid var(--mg-line); }
    .mg-run-grid a { min-height:120px; padding:21px; border-right:1px solid var(--mg-line); border-bottom:1px solid var(--mg-line); display:flex; flex-direction:column; justify-content:space-between; gap:20px; color:var(--mg-ink); text-decoration:none; transition:background .15s,transform .15s; }
    .mg-run-grid a:hover { background:rgba(var(--fg-rgb),.055); transform:translateY(-2px); }
    .mg-run-grid span { font-weight:800; line-height:1.25; }
    .mg-run-grid b { color:var(--mg-muted); font-size:.73rem; text-transform:uppercase; letter-spacing:.08em; }
    .mg-filters { display:grid; grid-template-columns:2fr 1.5fr .7fr .9fr; gap:14px; align-items:end; padding:20px; border:1px solid var(--mg-line); background:rgba(var(--fg-rgb),.025); }
    .mg-filters label { display:grid; gap:7px; color:var(--mg-muted); font-size:.73rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
    .mg-filters input[type=search],.mg-filters select { width:100%; min-height:44px; border:1px solid var(--mg-line); background:var(--bg); color:var(--mg-ink); padding:0 12px; font:inherit; font-size:.88rem; text-transform:none; letter-spacing:normal; border-radius:0; }
    .mg-check { display:flex!important; grid-column:1 / span 2; flex-direction:row; align-items:center; gap:9px!important; min-height:38px; }
    .mg-check input { width:18px; height:18px; accent-color:#efc34f; }
    .mg-filters button,.mg-clear { min-height:42px; display:inline-flex; justify-content:center; align-items:center; padding:0 19px; border:1px solid var(--mg-ink); background:var(--mg-ink); color:var(--bg); font-weight:850; cursor:pointer; text-decoration:none; }
    .mg-clear { background:transparent; color:var(--mg-ink); }
    .mg-results-line { display:flex; align-items:center; gap:13px; min-height:66px; border-bottom:1px solid var(--mg-line); }
    .mg-results-line p { margin:0; }
    .mg-results-line span { padding:5px 9px; background:rgba(var(--fg-rgb),.07); color:var(--mg-muted); font-size:.77rem; }
    .mg-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:28px 20px; margin-top:28px; }
    .mg-card { min-width:0; border:1px solid var(--mg-line); background:rgba(var(--fg-rgb),.018); display:grid; grid-template-columns:42% 58%; }
    .mg-cover { position:relative; min-height:300px; background:rgba(var(--fg-rgb),.055); overflow:hidden; }
    .mg-cover img { width:100%; height:100%; object-fit:cover; object-position:center top; display:block; }
    .mg-fallback { position:absolute; inset:0; padding:22px 17px; display:flex; flex-direction:column; justify-content:space-between; color:#131019; background:linear-gradient(145deg,#efc34f,#d87856); }
    .mg-fallback span { font-size:.65rem; font-weight:900; letter-spacing:.14em; text-transform:uppercase; }
    .mg-fallback b { font-size:2rem; }
    .mg-fallback i { font-size:1.4rem; font-weight:900; line-height:.9; font-style:normal; text-transform:uppercase; }
    .mg-scan-badge { position:absolute; left:9px; bottom:9px; padding:6px 8px; background:#17131f; color:#fff; font-size:.63rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; }
    .mg-card-copy { padding:20px; display:flex; flex-direction:column; align-items:flex-start; min-width:0; }
    .mg-meta { width:100%; margin:0 0 13px; display:flex; justify-content:space-between; gap:8px; color:var(--mg-muted); font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .mg-card h3 { margin:0; font-size:1.08rem; line-height:1.23; letter-spacing:-.02em; overflow-wrap:anywhere; }
    .mg-series { margin:9px 0 0; color:var(--mg-muted); font-size:.74rem; line-height:1.35; }
    .mg-description { margin:15px 0 18px; color:var(--mg-muted); font-size:.78rem; line-height:1.52; }
    .mg-read,.mg-unavailable { margin-top:auto; font-size:.76rem; font-weight:850; }
    .mg-read { color:var(--mg-ink); text-decoration:underline; text-underline-offset:4px; }
    .mg-unavailable { color:var(--mg-muted); line-height:1.4; }
    .mg-empty { margin-top:28px; padding:50px; text-align:center; border:1px solid var(--mg-line); }
    .mg-pagination { margin-top:42px; display:grid; grid-template-columns:1fr auto 1fr; align-items:center; padding:18px 0; border-top:1px solid var(--mg-line); border-bottom:1px solid var(--mg-line); }
    .mg-pagination>*:last-child { text-align:right; }
    .mg-pagination a { color:var(--mg-ink); font-weight:800; }
    .mg-pagination span { color:var(--mg-muted); }
    .mg-pagination b { font-size:.78rem; text-transform:uppercase; letter-spacing:.08em; }
    @media(max-width:1100px){.mg-run-grid{grid-template-columns:repeat(2,1fr)}.mg-grid{grid-template-columns:repeat(2,1fr)}.mg-filters{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:700px){.mg-page{padding-top:14px}.mg-hero{min-height:430px}.mg-covers{grid-template-columns:repeat(3,1fr)}.mg-covers span:nth-child(n+7){display:none}.mg-hero-copy{padding:30px 24px}.mg-stats{grid-template-columns:1fr}.mg-stats div{border-right:0;border-bottom:1px solid var(--mg-line)}.mg-stats div:last-child{border-bottom:0}.mg-intro{align-items:flex-start;flex-direction:column;margin-bottom:55px}.mg-run-grid,.mg-grid,.mg-filters{grid-template-columns:1fr}.mg-check{grid-column:auto}.mg-card{grid-template-columns:40% 60%}.mg-cover{min-height:275px}.mg-pagination{grid-template-columns:1fr 1fr}.mg-pagination b{grid-column:1/-1;grid-row:1;text-align:center;margin-bottom:18px}.mg-pagination a,.mg-pagination span{grid-row:2}}
</style>
@endsection
