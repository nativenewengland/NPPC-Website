@php
    $issueLabel = function ($record) {
        if ($record->volume) return $record->volume;
        if (preg_match('/Issue\s*#?\s*[\d\/.-]+/i', $record->title, $match)) return $match[0];
        if (preg_match('/No\.\s*[\d\/.-]+/i', $record->title, $match)) return $match[0];
        return ucfirst((string) ($record->source_format ?: $record->record_type));
    };
    $archiveUrl = fn ($section) => '/archive?'.http_build_query([
        'collection' => $section['archive_collection'],
        'include_nondigitized' => '1',
        'sort' => 'newest',
    ]);
@endphp

@extends('app')
@section('title', 'Magazines | NPPC')

@section('body')
<div class="line mt-8"></div>
<h1 class="text-6xl mt-12">Magazines</h1>

<article class="mp-page">
    <div class="mp-intro">
        <p>The NPPC Archive collects movement publications that documented political-prisoner cases, state repression, and the resistance traditions that sustained imprisoned dissidents. These periodicals preserved issue numbers, prisoner addresses, court updates, and movement analysis that the mainstream press often ignored.</p>
        <p>Every issue shown here is now supplied by the Archive. New records and corrected files will appear in their publication section automatically.</p>
    </div>

    @foreach ($primarySections as $section)
        <section class="mp-publication" aria-labelledby="publication-{{ $loop->index }}">
            <header class="mp-publication-head">
                <div>
                    <h2 id="publication-{{ $loop->index }}">{{ $section['name'] }}</h2>
                    @if (! empty($section['meta']))<p>{{ $section['meta'] }}</p>@endif
                </div>
                @if (! empty($section['website']))
                    <a href="{{ $section['website'] }}" target="_blank" rel="noopener">{{ $section['website_label'] }} <span aria-hidden="true">↗</span></a>
                @endif
            </header>
            <p class="mp-description">{{ $section['description'] }}</p>
            <div class="mp-issue-grid">
                @foreach ($section['records'] as $record)
                    @include('pages.partials.magazine-issue-card', ['record' => $record, 'issueLabel' => $issueLabel])
                @endforeach
            </div>
            @if ($section['total'] > $section['records']->count())
                <a class="mp-view-all" href="{{ $archiveUrl($section) }}">View all {{ number_format($section['total']) }} issues in the Archive <span aria-hidden="true">→</span></a>
            @endif
        </section>
    @endforeach

    <section class="mp-other" aria-labelledby="other-movement-press">
        <header class="mp-publication-head">
            <div><h2 id="other-movement-press">Other Movement Press</h2><p>Selected publication runs from the broader Archive</p></div>
        </header>
        @foreach ($additionalSections as $section)
            <section class="mp-secondary" aria-labelledby="other-publication-{{ $loop->index }}">
                <div class="mp-secondary-head">
                    <div><h3 id="other-publication-{{ $loop->index }}">{{ $section['name'] }}</h3><p>{{ $section['description'] }}</p></div>
                    <a href="{{ $archiveUrl($section) }}">All {{ number_format($section['total']) }} issues <span aria-hidden="true">→</span></a>
                </div>
                <div class="mp-issue-grid">
                    @foreach ($section['records'] as $record)
                        @include('pages.partials.magazine-issue-card', ['record' => $record, 'issueLabel' => $issueLabel])
                    @endforeach
                </div>
            </section>
        @endforeach
    </section>

    <section class="mp-submit">
        <h2>Submit a publication</h2>
        <p>If you have scans of relevant out-of-print movement publications, contact us through the <a href="/contact">contact page</a>. Please include the publication name, issue number, year, and any available provenance.</p>
    </section>
</article>

<style>
    .mp-page { max-width:1100px; margin:0 auto; padding:44px 0 70px; --mp-line:rgba(var(--fg-rgb),.2); --mp-muted:rgba(var(--fg-rgb),.7); }
    .mp-intro { max-width:760px; margin-bottom:54px; }
    .mp-intro p { margin:0 0 18px; font-size:1.04rem; line-height:1.72; }
    .mp-intro p:last-child { color:var(--mp-muted); font-size:.94rem; }
    .mp-publication,.mp-other { margin-bottom:64px; }
    .mp-publication-head { display:flex; align-items:flex-end; justify-content:space-between; gap:24px; padding-bottom:13px; margin-bottom:22px; border-bottom:2px solid var(--mp-line); }
    .mp-publication-head h2 { margin:0; font-size:1.5rem; font-weight:850; letter-spacing:.035em; text-transform:uppercase; }
    .mp-publication-head p { margin:6px 0 0; color:var(--mp-muted); font-size:.78rem; }
    .mp-publication-head a,.mp-secondary-head>a { flex:0 0 auto; color:var(--fg); font-size:.78rem; font-weight:750; text-decoration:none; }
    .mp-description { max-width:740px; margin:0 0 25px; color:var(--mp-muted); font-size:.9rem; line-height:1.65; }
    .mp-issue-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .mp-card { position:relative; min-width:0; padding:14px; border:1px solid var(--mp-line); border-radius:4px; color:inherit; text-decoration:none; transition:border-color .15s,background .15s,transform .15s; }
    a.mp-card:hover { border-color:rgba(var(--fg-rgb),.55); background:rgba(var(--fg-rgb),.035); transform:translateY(-2px); }
    .mp-cover { position:relative; width:100%; aspect-ratio:1/1; margin-bottom:13px; overflow:hidden; background:rgba(var(--fg-rgb),.06); border:1px solid rgba(var(--fg-rgb),.1); }
    .mp-cover img { width:100%; height:100%; object-fit:cover; object-position:center top; display:block; }
    .mp-cover-fallback { position:absolute; inset:0; display:flex; flex-direction:column; justify-content:space-between; padding:17px; background:linear-gradient(145deg,rgba(var(--fg-rgb),.13),rgba(var(--fg-rgb),.035)); }
    .mp-cover-fallback span { font-size:.63rem; font-weight:850; letter-spacing:.12em; text-transform:uppercase; }
    .mp-cover-fallback b { font-size:1.55rem; line-height:1; overflow-wrap:anywhere; }
    .mp-card-kicker { margin:0; color:var(--mp-muted); font-size:.68rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .mp-card h3 { margin:6px 0 0; font-size:.96rem; line-height:1.35; font-weight:800; }
    .mp-scan { position:absolute; left:9px; bottom:9px; padding:5px 7px; background:#18151d; color:#fff; font-size:.58rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .mp-view-all { display:inline-flex; margin-top:20px; color:var(--fg); font-size:.82rem; font-weight:800; text-underline-offset:4px; }
    .mp-secondary { margin-top:36px; padding-bottom:42px; border-bottom:1px solid var(--mp-line); }
    .mp-secondary:last-child { border-bottom:0; }
    .mp-secondary-head { display:flex; align-items:flex-start; justify-content:space-between; gap:26px; margin-bottom:20px; }
    .mp-secondary-head h3 { margin:0; font-size:1.12rem; font-weight:850; }
    .mp-secondary-head p { max-width:720px; margin:6px 0 0; color:var(--mp-muted); font-size:.82rem; line-height:1.55; }
    .mp-submit { margin-top:70px; padding:25px; border:1px dashed rgba(var(--fg-rgb),.32); border-radius:4px; }
    .mp-submit h2 { margin:0 0 10px; font-size:1rem; font-weight:850; letter-spacing:.08em; text-transform:uppercase; }
    .mp-submit p { margin:0; color:var(--mp-muted); font-size:.88rem; line-height:1.65; }
    .mp-submit a { color:var(--fg); }
    @media(max-width:950px){.mp-issue-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:700px){.mp-page{padding-top:34px}.mp-publication-head,.mp-secondary-head{align-items:flex-start;flex-direction:column;gap:10px}.mp-issue-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}.mp-card{padding:10px}.mp-publication{margin-bottom:52px}}
    @media(max-width:380px){.mp-issue-grid{grid-template-columns:1fr}}
</style>
@endsection
