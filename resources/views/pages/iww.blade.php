@extends('app')

@section('title', 'IWW: Organizing, Free Speech & Prisoner Histories | NPPC')
@section('meta_description', 'Explore Industrial Workers of the World history through a map, searchable timeline, historical union locals, labor newspapers and NPPC prisoner records.')
@section('og_image', asset('images/iww-history/lawrence-1912.jpg'))
@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="/style/iww-history.css?v={{ filemtime(public_path('style/iww-history.css')) }}">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="/js/iww-history.js?v={{ filemtime(public_path('js/iww-history.js')) }}" defer></script>
@endsection

@section('body')
@php
    $sources = $history['sources'];
    $cities = collect($history['cities'])->keyBy('id');
    $kinds = ['organizing' => 'Organizing', 'strike' => 'Strikes', 'speech' => 'Free speech', 'repression' => 'Repression & defense'];
    $databaseUrl = '/database/affiliation/industrial-workers-of-the-world-iww';
@endphp
<article class="iww" id="iww-history">
    <header class="iww-hero">
        <div class="iww-hero-copy">
            <a class="iww-back" href="/learn-more">Learn More / Movement histories</a>
            <p class="iww-eyebrow">Labor solidarity · Since 1905</p>
            <h1>IWW<span>.</span><small>Industrial Workers<br>of the World</small></h1>
            <p class="iww-deck">One big union.<br>Thousands of individual stories.</p>
            <p class="iww-hero-intro">From the mill gate to the jail cell: organizing, free speech and the struggle for worker power.</p>
            <div class="iww-actions"><a class="iww-button" href="#timeline">Explore the history <span aria-hidden="true">↘</span></a><a class="iww-text-link" href="#people">Meet the people →</a></div>
        </div>
        <figure class="iww-hero-photo"><img src="/images/iww-history/lawrence-1912.jpg" alt="Textile strikers gather in a snowy street outside mill buildings in Lawrence, Massachusetts, in 1912." width="1024" height="743" fetchpriority="high"><figcaption><span>Lawrence, Massachusetts · 1912</span><a href="#image-credit">Bain News Service / Library of Congress ↗</a></figcaption></figure>
    </header>
    <nav class="iww-section-nav" aria-label="On this page"><a href="#movement">The movement</a><a href="#timeline">Map & timeline</a><a href="#locals">Union locals</a><a href="#solidarity">Solidarity in practice</a><a href="#people">People & cases</a><a href="#press">The labor press</a><a href="#sources">Sources</a></nav>
    <div class="iww-stats">
        <a href="{{ $sources['founding']['url'] }}"><strong>1905</strong><span>Founding convention · Chicago ↗</span></a>
        <a href="#locals"><strong>928</strong><span>Locals, branches & councils in UW’s directory ↘</span></a>
        <a href="{{ $databaseUrl }}"><strong>{{ number_format($recordCount) }}</strong><span>IWW-affiliated profiles, including minor cases ↗</span></a>
    </div>
    <section class="iww-section iww-introduction" id="movement">
        <div><p class="iww-eyebrow">01 / The movement</p><h2>Across trades.<br>Across languages.<br>Across borders.</h2></div>
        <div class="iww-prose"><p>The Industrial Workers of the World set out to organize workers together across industrial and craft divisions. Founded in Chicago in 1905, it connected immediate workplace demands with a far-reaching goal: workers’ control over the conditions and products of their labor. <a class="iww-cite" href="{{ $sources['founding']['url'] }}">Founding proceedings ↗</a></p><p>Known as Wobblies, members organized in mills, mines, forests, fields and ports. Strikes and street meetings were joined by newspapers, songs, relief work and legal defense. Their campaigns repeatedly brought them into conflict with employers, police and government authorities. <a class="iww-cite" href="{{ $sources['uw']['url'] }}">UW history ↗</a></p><p>This page follows the formative years, 1905–1924, and connects that history to NPPC’s individual prisoner records. The IWW continues today; its early history is one part of a longer movement. <a class="iww-cite" href="{{ $sources['today']['url'] }}">The IWW today ↗</a></p></div>
    </section>
    <section class="iww-section" id="timeline">
        <div class="iww-heading"><div><p class="iww-eyebrow">02 / Places, campaigns & cases</p><h2>Follow the struggle.</h2></div><p>{{ count($history['events']) }} selected milestones in {{ count($cities) }} places. Choose a city or a theme, then open an event for its sources and related records.</p></div>
        <form class="iww-filters" role="search" aria-label="Filter IWW history" hidden>
            <label for="iww-search">Search this history<input id="iww-search" type="search" maxlength="160" placeholder="Try Fletcher, textile, free speech…"></label>
            <label for="iww-city">City<select id="iww-city"><option value="all">All event cities</option>@foreach($history['cities'] as $city)<option value="{{ $city['id'] }}">{{ $city['name'] }}, {{ $city['state'] }}</option>@endforeach</select></label>
            <label for="iww-kind">Theme<select id="iww-kind"><option value="all">All themes</option>@foreach($kinds as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            <label for="iww-period">Period<select id="iww-period"><option value="all">1905–1924</option><option value="early">1905–1911</option><option value="growth">1912–1916</option><option value="war">1917–1924</option></select></label>
            <button type="reset" class="iww-reset">Reset filters</button>
        </form>
        <div class="iww-map-wrap" hidden><div id="iww-map" aria-label="Map of {{ count($cities) }} selected IWW event locations"></div><p>Markers locate communities, not exact incident sites. The timber strike and federal raids extended beyond the reference cities shown. For the complete historical local-union research map, see <a href="#locals">Union locals below</a>.</p></div>
        <div class="iww-timeline-toolbar"><p class="iww-results" role="status" aria-live="polite">{{ count($history['events']) }} milestones</p><button type="button" class="iww-reset" data-expand hidden>Expand visible events</button></div>
        <div class="iww-timeline">
            @foreach($history['events'] as $event)
            <details class="iww-event" id="event-{{ $event['id'] }}" data-city="{{ $event['city'] }}" data-kind="{{ $event['kind'] }}" data-year="{{ $event['year'] }}" @if($loop->first) open @endif>
                <summary><span class="iww-event-date">{{ $event['date'] }}</span><span><span class="iww-event-meta">{{ $cities[$event['city']]['name'] }} / {{ $kinds[$event['kind']] }}</span><span class="iww-event-title">{{ $event['title'] }}</span></span><span class="iww-plus" aria-hidden="true">+</span></summary>
                <div class="iww-event-body"><p>{{ $event['body'] }}</p><div class="iww-event-links">@foreach($event['sources'] as $key)<a class="iww-cite" href="{{ $sources[$key]['url'] }}">{{ $sources[$key]['label'] }} ↗</a>@endforeach @if($event['topic'] && $topics->has($event['topic']))<a class="iww-text-link" href="/topics/{{ $event['topic'] }}">Read the NPPC topic →</a>@endif</div>
                    @if(count($event['profiles']))<div class="iww-related">@foreach($event['profiles'] as $slug)@if($profiles->has($slug))<a href="{{ $profiles[$slug]->url }}">{{ $profiles[$slug]->name }} <span aria-hidden="true">↗</span></a>@endif @endforeach</div>@endif
                </div>
            </details>
            @endforeach
        </div>
        <div class="iww-empty" hidden><h3>No milestones match those filters.</h3><p>Try a different city, period or search term.</p><button class="iww-button" type="button" data-reset>Show all milestones →</button></div>
        <p class="iww-method">Dates retain the precision of the sources; broad period filters use the starting year. Newspaper research sometimes records a report’s publication date rather than the event date. These summaries use the identified event date or a broader year, with links for further reading. Coordinates: <a href="{{ $history['coordinate_source'] }}">U.S. Census place Gazetteer</a>.</p>
    </section>
    <section class="iww-section iww-locals" id="locals">
        <div class="iww-heading"><div><p class="iww-eyebrow">03 / The wider geography</p><h2>A network across<br>North America.</h2></div><p>The University of Washington’s directory identifies 928 locals, branches and district councils in more than 350 cities and towns in the United States and Canada. <a class="iww-cite" href="{{ $sources['directory']['url'] }}">Explore the directory ↗</a></p></div>
        <div class="iww-locals-intro"><div><h3>Explore the historical union network.</h3><p>The full UW map offers views by place and industry. Recorded active periods describe the surviving evidence, not necessarily a local’s founding or closing date. Points are approximate city locations.</p><p class="iww-map-credit">Research: Arianne Hermida and James Gregory · IWW History Project, University of Washington.</p></div><button type="button" class="iww-button" id="iww-load-locals" hidden>Load the full locals map <span aria-hidden="true">↘</span></button></div>
        <div class="iww-external-map" id="iww-locals-frame" data-map-url="{{ $history['locals_embed'] }}" hidden></div>
        <div class="iww-actions iww-map-links"><a class="iww-text-link" href="{{ $sources['locals']['url'] }}" target="_blank" rel="noopener">Open UW’s map in a new tab ↗</a><a class="iww-text-link" href="{{ $sources['directory']['url'] }}">Read the complete local-union list ↗</a></div>
        <p class="iww-method">The historical map is provided by UW through Tableau Public. Its own filters operate separately from the NPPC timeline. The map and directory links remain available if the embedded view cannot load.</p>
    </section>
    <section class="iww-section" id="solidarity">
        <div class="iww-heading"><div><p class="iww-eyebrow">04 / Solidarity in practice</p><h2>How a union<br>became a movement.</h2></div><p>Organization had to cross the divisions of the workplace—and survive when its members were jailed.</p></div>
        <div class="iww-practice-grid">
            <article><span class="iww-card-number">01</span><h3>Industrial unionism</h3><p>The IWW argued that workers in the same industry should organize together. Its preamble connects collective workplace power with a wider transformation of economic life.</p><a class="iww-cite" href="{{ $sources['preamble']['url'] }}">Read the IWW preamble ↗</a></article>
            <article><span class="iww-card-number">02</span><h3>Interracial organizing</h3><p>Philadelphia’s Local 8 made Black and white longshore workers part of a common organization. Ben Fletcher’s leadership shows how solidarity could challenge racial exclusion in the labor movement.</p><a class="iww-cite" href="{{ $sources['local8']['url'] }}">Peter Cole on Local 8 ↗</a></article>
            <article><span class="iww-card-number">03</span><h3>Workers across borders</h3><p>Mexican workers brought their own political traditions, networks and organizing experience to the IWW. Connections with the Partido Liberal Mexicano place this history on both sides of the border.</p><a class="iww-cite" href="{{ $sources['mexican']['url'] }}">Devra Weber’s research ↗</a></article>
            <article><span class="iww-card-number">04</span><h3>Speech & prisoner defense</h3><p>Public speaking, strike support and legal defense became connected struggles. The Everett prosecutions show how a local confrontation could become a national campaign over evidence, responsibility and freedom.</p><a class="iww-cite" href="{{ $sources['everett']['url'] }}">Explore the Everett records ↗</a></article>
        </div>
    </section>
    <section class="iww-section iww-repression">
        <p class="iww-eyebrow">05 / Repression & its aftermath</p><h2>When organizing<br>became a prosecution.</h2>
        <div class="iww-prose"><p>The 1917 federal raids turned membership records, correspondence and publications into material for mass prosecutions. Wartime law and surveillance disrupted the union’s leadership and everyday organization. <a class="iww-cite" href="{{ $sources['justice']['url'] }}">Steven Parfitt’s account ↗</a></p><p>Repression did not act alone. Disagreements about leadership and strategy helped produce the 1924 split. Studying both the state’s campaign and the union’s internal conflicts makes its survival—and its losses—more understandable. <a class="iww-cite" href="{{ $sources['split']['url'] }}">Read about the split ↗</a></p><a class="iww-button" href="{{ $databaseUrl }}">Explore IWW prisoner records →</a></div>
    </section>
    <section class="iww-section" id="people">
        <div class="iww-heading"><div><p class="iww-eyebrow">06 / People & case histories</p><h2>Behind every case,<br>a person.</h2></div><p>Selected people already documented by NPPC. Each profile connects to its biography and recorded cases; those cases may span more than one movement or period.</p></div>
        <div class="iww-people">@foreach($history['profile_order'] as $slug)@if($profiles->has($slug))@php $person = $profiles[$slug]; @endphp<a class="iww-person" href="{{ $person->url }}">@if($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" decoding="async">@else<div class="iww-portrait-placeholder" aria-hidden="true"><span>IWW</span><small>Portrait unavailable</small></div>@endif<div><h3>{{ $person->name }}</h3><span>Profile & case history ↗</span></div></a>@endif @endforeach</div>
        <a class="iww-text-link iww-all-records" href="{{ $databaseUrl }}">Browse IWW-affiliated profiles →</a>
    </section>
    <section class="iww-section" id="press">
        <div class="iww-heading"><div><p class="iww-eyebrow">07 / The labor press</p><h2>A movement<br>in many languages.</h2></div><p>Kenyon Zimmer’s research for UW identifies 90 IWW periodicals in 19 languages. Newspapers connected workers across distance and preserved the arguments within the movement. <a class="iww-cite" href="{{ $sources['papers']['url'] }}">Newspaper research ↗</a></p></div>
        <div class="iww-press-grid"><article><p class="iww-paper-label">The printed record</p><h3>Newspapers & periodicals</h3><p>Explore titles, languages, places of publication and links to digitized issues.</p><a class="iww-text-link" href="{{ $sources['papers']['url'] }}">Read the newspaper directory ↗</a></article><article><p class="iww-paper-label">Day by day</p><h3>Campaign yearbooks</h3><p>Follow contemporary reports of strikes, meetings, arrests and solidarity campaigns. Read their source notes alongside the entries.</p><a class="iww-text-link" href="{{ $sources['yearbooks']['url'] }}">Browse UW’s yearbooks ↗</a></article><article><p class="iww-paper-label">Culture & memory</p><h3>Joe Hill’s songs</h3><p>Songwriting carried workplace experience into rallies and organizing drives. Hill’s life and prosecution became part of that shared memory.</p><a class="iww-text-link" href="{{ $sources['hill']['url'] }}">Explore Joe Hill’s history ↗</a></article></div>
    </section>
    <section class="iww-section iww-sources" id="sources">
        <div><p class="iww-eyebrow">08 / Follow the evidence</p><h2>History you<br>can trace.</h2><p>Inspired by the University of Washington’s IWW History Project, directed by James Gregory and Conor Casey. NPPC’s summaries connect that research with union documents, public archives and existing prisoner records.</p><p>The full local-union map remains hosted by its researchers. Sources are linked at the point of use; the list here provides a route into the wider archive.</p></div>
        <details><summary>Research, archives & source list</summary><ol>@foreach($sources as $key => $source)@if($key !== 'photo')<li><a href="{{ $source['url'] }}">{{ $source['label'] }} ↗</a></li>@endif @endforeach</ol></details>
        <p id="image-credit" class="iww-image-credit">Header photograph: Lawrence textile strike, 1912. Bain News Service, Library of Congress, LC-DIG-ggbain-10150. <a href="{{ $sources['photo']['url'] }}">Original catalog record</a>. No known restrictions on publication. Original image retained; framing varies with screen size.</p>
    </section>
    <script type="application/json" id="iww-map-data">{!! json_encode($history['cities'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</article>
@endsection
