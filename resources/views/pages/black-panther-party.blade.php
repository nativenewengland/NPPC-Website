@extends('app')

@section('title', 'The Black Panther Party: History, Places & People | NPPC')
@section('meta_description', 'Explore Black Panther Party history through an interactive city map, a searchable timeline, community survival programs, primary sources and NPPC prisoner profiles.')
@section('og_image', asset('images/black-panther-history/olympia-1969.jpg'))
@section('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="/style/black-panther-history.css?v={{ filemtime(public_path('style/black-panther-history.css')) }}">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="/js/black-panther-history.js?v={{ filemtime(public_path('js/black-panther-history.js')) }}" defer></script>
@endsection

@section('body')
@php
    $sources = $history['sources'];
    $cityNames = collect($history['cities'])->pluck('name', 'id');
    $cityStories = collect($history['cities'])->keyBy('id');
    $locations = $atlas['locations'];
    $kinds = ['organizing' => 'Organizing', 'community' => 'Community programs', 'repression' => 'State repression'];
@endphp
<article class="bpp" id="bpp-history">
    <header class="bpp-hero">
        <div class="bpp-hero-copy">
            <a class="bpp-back" href="/learn-more">Learn More <span aria-hidden="true">/</span> Movement histories</a>
            <p class="bpp-eyebrow">Black liberation · Since 1966</p>
            <h1>THE BLACK<br>PANTHER<br><span>PARTY.</span></h1>
            <p class="bpp-deck">A movement for self-determination.<br>A history that reaches beyond the headlines.</p>
            <div class="bpp-hero-actions"><a class="bpp-button" href="#places">Explore the history <span aria-hidden="true">↘</span></a><a class="bpp-text-link" href="#people">Meet the people <span aria-hidden="true">→</span></a></div>
        </div>
        <figure class="bpp-hero-photo">
            <img src="/images/black-panther-history/olympia-1969.jpg" alt="Seattle Black Panther Party members stand on the steps of the Washington State Capitol in Olympia, February 28, 1969." width="1920" height="1372" fetchpriority="high">
            <figcaption><span>Olympia, Washington · 28 February 1969</span><a href="#image-credit">Photo credit ↗</a></figcaption>
        </figure>
    </header>
    <nav class="bpp-section-nav" aria-label="On this page"><a href="#overview">Overview</a><a href="#places">Places & timeline</a><a href="#programs">Survival programs</a><a href="#platform">Ten-Point Program</a><a href="#people">People & cases</a><a href="#sources">Sources</a></nav>
    <section id="overview" class="bpp-section bpp-overview">
        <div><p class="bpp-eyebrow">01 / The movement</p><h2>Self-defense.<br>Community.<br>Political power.</h2></div>
        <div class="bpp-prose"><p>Founded in Oakland in October 1966 by Huey P. Newton and Bobby Seale, the Black Panther Party organized against police brutality and for Black self-determination. Its politics connected struggles in the United States with liberation movements abroad. <a class="bpp-cite" href="{{ $sources['uw-intro']['url'] }}">[UW]</a></p><p>Members built programs for food, health care, education and legal support. Women sustained much of this daily organizing and held leadership positions, while also confronting inequality within the Party. <a class="bpp-cite" href="{{ $sources['smithsonian']['url'] }}">[Smithsonian]</a></p><p>This NPPC history connects places, organizing and repression with the lives recorded in our prisoner database. Explore the sources alongside the stories.</p></div>
    </section>
    <section id="places" class="bpp-section">
        <div class="bpp-section-heading"><div><p class="bpp-eyebrow">02 / Across the country & beyond</p><h2>A national movement.<br>Local histories.</h2></div><p>Explore {{ count($locations) }} documented locations: chapters, branches and affiliated organizing centers, plus the International Section in Algiers.</p></div>
        <div class="bpp-city-picker" hidden><label for="bpp-city">Find a city or locality</label><select id="bpp-city"><option value="all">All cities · {{ count($locations) }} locations</option>@foreach($locations as $location)<option value="{{ $location['id'] }}">{{ $location['name'] }} — {{ $location['state'] }}</option>@endforeach</select><p>Choose any location below, or zoom in and select its marker.</p></div>
        <div class="bpp-city-buttons" aria-label="Choose a city" hidden><button type="button" data-city="all" aria-pressed="true">All cities</button>@foreach($history['cities'] as $city)<button type="button" data-city="{{ $city['id'] }}" aria-pressed="false">{{ $city['name'] }}</button>@endforeach</div>
        <div class="bpp-atlas">
            <div class="bpp-map-wrap" hidden><div id="bpp-map" aria-label="Map of {{ count($locations) }} documented Black Panther Party chapter and organizing locations"></div><p class="bpp-map-note">One marker per city or locality. Zoom in to separate nearby markers. Points locate communities, not historical office or prison addresses. The city selector provides keyboard access to every location.</p></div>
            <div class="bpp-city-stories">
                <div class="bpp-atlas-intro" hidden><div><p class="bpp-eyebrow">Many cities. Connected histories.</p><h3>Choose a place to begin.</h3><p>Every marker opens a documented local presence and its sources. The six city shortcuts also include longer histories and selected milestones.</p></div><div class="bpp-atlas-total"><span class="bpp-atlas-number" aria-hidden="true">{{ count($locations) }}</span><span>documented locations</span></div></div>
                @foreach($locations as $location)
                @php $city = $cityStories->get($location['id']); @endphp
                <section class="bpp-city-story" id="place-{{ $location['id'] }}" data-story="{{ $location['id'] }}" tabindex="-1">
                    <p class="bpp-eyebrow">{{ $location['state'] }} · {{ $location['label'] }}</p><h3>{{ $location['name'] }}</h3>
                    @if($city)<h4>{{ $city['heading'] }}</h4><p>{{ $city['body'] }}</p><a class="bpp-cite" href="{{ $sources[$city['source']]['url'] }}">Read the city history ↗</a>@endif
                    <div class="bpp-location-evidence"><p>{{ $location['note'] }}</p><ul>@foreach($location['evidence'] as $reference)<li><a class="bpp-cite" href="{{ $atlas['sources'][$reference]['url'] }}">{{ $atlas['sources'][$reference]['label'] }} ↗</a></li>@endforeach</ul></div>
                    @if($city)<div class="bpp-city-people">@foreach($city['profiles'] as $slug)@if($profiles->has($slug))<a href="{{ $profiles[$slug]->url }}">{{ $profiles[$slug]->name }} <span aria-hidden="true">↗</span></a>@endif @endforeach</div>@endif
                </section>
                @endforeach
            </div>
        </div>
        <details class="bpp-city-directory"><summary>Browse all {{ count($locations) }} locations</summary><ul>@foreach($locations as $location)<li><a href="?city={{ $location['id'] }}#place-{{ $location['id'] }}" data-place="{{ $location['id'] }}"><strong>{{ $location['name'] }}</strong><span>{{ $location['state'] }} · {{ $location['label'] }}</span></a></li>@endforeach</ul></details>
        <details class="bpp-map-method"><summary>What the map includes & how to read it</summary><p>{{ $atlas['coverage'] }}</p><p>These locations span different years; they were not necessarily active at the same time. A marker does not always mean a formally chartered chapter. Each location identifies the organization described by its source. The historical directory is partial, so this is a documented map that can grow as further locations are verified.</p><p>The 1971 government directory combines Party newspaper lists with police reports. We use its location table and contemporary memoranda alongside Party newspapers, participant accounts and local archives to identify places. Government sources’ political characterizations are not adopted here.</p><ul>@foreach($atlas['sources'] as $source)<li><a href="{{ $source['url'] }}">{{ $source['label'] }} ↗</a></li>@endforeach</ul><p>{{ $atlas['coordinate_source']['note'] }} Coordinates: <a href="{{ $atlas['coordinate_source']['url'] }}">U.S. Census Bureau</a> and <a href="https://www.geonames.org/">GeoNames</a> (<a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a>).</p></details>
        <div class="bpp-timeline-heading"><h3>Follow the turning points</h3><p>Selected milestones, 1966–1981. Dates retain the precision of the cited sources.</p></div>
        <form class="bpp-filters" role="search" aria-label="Filter historical milestones" hidden>
            <label>Search this history<input id="bpp-search" type="search" placeholder="Try education, Hampton, New York…" maxlength="160"></label>
            <label>Theme<select id="bpp-kind"><option value="all">All themes</option>@foreach($kinds as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
            <label>Period<select id="bpp-period"><option value="all">All years</option><option value="early">1966–1968</option><option value="middle">1969–1971</option><option value="later">1972–1981</option></select></label>
            <button type="reset" class="bpp-reset">Reset filters</button>
        </form>
        <p class="bpp-results" role="status" aria-live="polite">{{ count($history['events']) }} milestones</p>
        <div class="bpp-timeline">
            @foreach($history['events'] as $event)
            <article class="bpp-event" id="event-{{ $event['id'] }}" data-city="{{ $event['city'] }}" data-kind="{{ $event['kind'] }}" data-year="{{ $event['year'] }}">
                <div class="bpp-event-date">{{ $event['date'] }}</div><div class="bpp-event-body"><p class="bpp-event-meta">{{ $cityNames[$event['city']] }} <span aria-hidden="true">/</span> {{ $kinds[$event['kind']] }}</p><h4>{{ $event['title'] }}</h4><p>{{ $event['body'] }}</p><a class="bpp-cite" href="{{ $sources[$event['source']]['url'] }}">Source ↗</a></div>
            </article>
            @endforeach
        </div>
        <div class="bpp-empty" hidden><h4>No selected milestones match those filters.</h4><p>The timeline covers selected events, not every mapped location. Read this location’s sources above, or select all cities and years.</p><button type="button" class="bpp-button" data-reset>Show all milestones</button></div>
        <p class="bpp-method-note">This is a curated introduction. For a larger newspaper-based event dataset, explore the <a href="{{ $sources['uw-events']['url'] }}">University of Washington’s research ↗</a>. Its authors explain that news coverage favors dramatic incidents and can underrepresent everyday organizing.</p>
    </section>
    <section id="programs" class="bpp-section bpp-program-section">
        <div class="bpp-section-heading"><div><p class="bpp-eyebrow">03 / Serve the people</p><h2>Freedom also meant<br>breakfast. Care. School.</h2></div><p>Survival programs met immediate needs while making a political argument about what communities should be able to expect.</p></div>
        <div class="bpp-program-grid">
            <article><span class="bpp-program-number">01</span><h3>Food & daily support</h3><p>Breakfast for children, groceries, clothing and transportation connected political organizing with the daily needs of families and elders.</p><a class="bpp-cite" href="{{ $sources['smithsonian']['url'] }}">Smithsonian ↗</a></article>
            <article><span class="bpp-program-number">02</span><h3>Community health</h3><p>Free clinics and sickle-cell testing helped make health care part of the Party’s community work.</p><a class="bpp-cite" href="{{ $sources['smithsonian']['url'] }}">Smithsonian ↗</a></article>
            <article><span class="bpp-program-number">03</span><h3>Education & child care</h3><p>The Oakland Community School connected academic learning with meals, creativity and care. Ericka Huggins’s oral history describes a school organized around children’s needs.</p><a class="bpp-cite" href="{{ $sources['huggins']['url'] }}">Read the oral history ↗</a></article>
        </div>
    </section>
    <section class="bpp-section bpp-repression">
        <div><p class="bpp-eyebrow">04 / The state’s response</p><h2>Surveillance.<br>Raids. Imprisonment.</h2></div>
        <div class="bpp-prose"><p>FBI surveillance and counterintelligence records form part of the Party’s history. The National Archives brings together files on COINTELPRO, civil-rights litigation and the killings of Party members. <a class="bpp-cite" href="{{ $sources['nara']['url'] }}">[National Archives]</a></p><p>The December 1969 killing of Fred Hampton and Mark Clark in Chicago is one documented example. Records describe an FBI informant’s role and a police raid that also led to charges against surviving Panthers; those charges were later dropped. <a class="bpp-cite" href="{{ $sources['hampton']['url'] }}">[Hampton records]</a></p><p>Party members also confronted internal conflict. Huggins’s testimony describes both the school’s achievements and abuses of leadership. Reading participant accounts alongside government files makes space for this difficult history. <a class="bpp-cite" href="{{ $sources['huggins']['url'] }}">[Oral history]</a></p><a class="bpp-text-link" href="/database/affiliation/black-panther-party">Explore Panther prisoner records →</a></div>
    </section>
    <section id="platform" class="bpp-section">
        <div class="bpp-section-heading"><div><p class="bpp-eyebrow">05 / What the Party demanded</p><h2>The Ten-Point Program.</h2></div><p>A reading guide to the 1966 platform. These summaries are paraphrases; <a href="{{ $sources['platform']['url'] }}">read the historical text ↗</a>.</p></div>
        <div class="bpp-platform">@foreach($history['platform'] as $point)<details><summary><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $point['title'] }}<span class="bpp-expand" aria-hidden="true">+</span></summary><p>{{ $point['body'] }}</p></details>@endforeach</div>
    </section>
    <section id="people" class="bpp-section">
        <div class="bpp-section-heading"><div><p class="bpp-eyebrow">06 / People, not just names</p><h2>Follow the individual story.</h2></div><p>Selected people with records in the NPPC database. Open a profile for its biography, sources and case history.</p></div>
        <div class="bpp-people">@foreach($history['profile_order'] as $slug)@if($profiles->has($slug))@php $person = $profiles[$slug]; @endphp<a class="bpp-person" href="{{ $person->url }}">@if($person->photoUrl())<img src="{{ $person->photoUrl() }}" alt="" loading="lazy" decoding="async">@else<div class="bpp-person-placeholder" aria-hidden="true">NPPC</div>@endif<div><h3>{{ $person->name }}</h3><span>View profile & case history ↗</span></div></a>@endif @endforeach</div>
        <a class="bpp-button bpp-database-button" href="/database/affiliation/black-panther-party">Browse Panther records <span aria-hidden="true">→</span></a>
    </section>
    <section id="sources" class="bpp-section bpp-sources">
        <div><p class="bpp-eyebrow">07 / Read further</p><h2>History you can trace.</h2><p>Inspired by the University of Washington’s Mapping American Social Movements project. This page uses original NPPC summaries and a sourced location directory; the linked archives provide the underlying research and firsthand accounts.</p></div>
        <ol>@foreach($sources as $key => $source)@if($key !== 'photo')<li><a href="{{ $source['url'] }}">{{ $source['label'] }} <span aria-hidden="true">↗</span></a></li>@endif @endforeach</ol>
        <p id="image-credit" class="bpp-image-credit">Header photograph: Seattle Panthers at the Washington State Capitol, 28 February 1969. Washington State Archives, State Governors’ Negative Collection; shared by CIR Online. <a href="{{ $sources['photo']['url'] }}">Source</a> · <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a>. Photograph cropped for display.</p>
    </section>
    <script type="application/json" id="bpp-map-data">{!! json_encode($locations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</article>
@endsection
