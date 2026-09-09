@extends('app')

@section('title'){{ $prisoner->name }} — National Political Prisoner Coalition @endsection

@section('meta_description'){{ $prisoner->name }}{{ $prisoner->aka ? ' (AKA '.$prisoner->aka.')' : '' }} — {{ substr(strip_tags($prisoner->description ?? ''), 0, 155) }}@endsection

@if($prisoner->photo)
@section('og_image'){{ $prisoner->photoUrl() }}@endsection
@endif

@section('head')
<meta name="description" content="{{ $prisoner->name }}{{ $prisoner->aka ? ' (AKA '.$prisoner->aka.')' : '' }} — {{ substr(strip_tags($prisoner->description ?? ''), 0, 155) }}">
<meta property="og:title" content="{{ $prisoner->name }} — NPPC">
<meta property="og:description" content="{{ substr(strip_tags($prisoner->description ?? ''), 0, 200) }}">
@if($prisoner->photo)<meta property="og:image" content="{{ $prisoner->photoUrl() }}">@endif
<meta property="og:url" content="{{ url('/prisoner/'.$prisoner->slug) }}">
<meta property="og:type" content="profile">
<style>
    .deceased-indicator { cursor: help; }
    .deceased-indicator .deceased-label { display: none; }
    .deceased-indicator:hover .deceased-label { display: inline; }
    /* Raised dagger next to the age. Kept in step with the Vue database
       card (vue/src/components/database/CardComponent.vue), which has had
       the sup treatment since the old absolutely-nudged span was dropped;
       this page still rendered it full-size on the baseline. Explicit
       values so a CSS reset cannot flatten it back down. */
    sup.deceased-indicator { font-size: 0.65em; line-height: 0; position: relative; top: -0.45em; vertical-align: baseline; margin-left: 0.15em; }
    /* The site's own face, loaded globally from /fonts/verlag/stylesheet.css.
       This page used to hard-code Avenir, which is not licensed here and is
       absent on Windows and Android, so it silently fell back to Helvetica or
       Arial and read as a different site from the rest of the archive. */
    .prisoner-page { max-width: 1100px; margin: 0 auto; padding: 0 24px; font-family: Verlag A, Verlag B, Verlag, Helvetica, Arial, sans-serif; }
    .prisoner-hero { display: flex; gap: 48px; padding: 48px 0 40px; align-items: flex-start; }
    .prisoner-info { flex: 1; }
    .prisoner-photo-col { flex: 0 0 380px; }
    .prisoner-photo { width: 100%; border-radius: 8px; overflow: hidden; }
    .prisoner-photo img { width: 100%; height: auto; display: block; }
    .prisoner-photo-placeholder { width: 100%; aspect-ratio: 3/4; background: linear-gradient(135deg, #111 0%, #1a1a2e 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; }
    .prisoner-name { font-size: 3rem; font-weight: 900; color: var(--fg); line-height: 1.1; margin-bottom: 8px; }
    .prisoner-aka { font-size: 16px; color: rgba(var(--fg-rgb),0.5); margin-bottom: 24px; font-style: italic; }
    .prisoner-meta { margin-bottom: 32px; }
    .prisoner-meta-row { display: flex; margin-bottom: 6px; font-size: 15px; line-height: 1.5; }
    .prisoner-meta-label { font-weight: 700; color: var(--fg); min-width: 160px; flex-shrink: 0; }
    .prisoner-meta-value { color: rgba(var(--fg-rgb),0.7); }
    .prisoner-status-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; }
    .prisoner-badge { padding: 4px 14px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; border-radius: 4px; }
    .prisoner-badge-custody { background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }
    .prisoner-badge-released { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
    .prisoner-badge-exile { background: rgba(234,179,8,0.15); color: #eab308; border: 1px solid rgba(234,179,8,0.3); }
    .prisoner-badge-trial { background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); }

    /* Counter */
    .prisoner-counter-label { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(var(--fg-rgb),0.5); margin-bottom: 8px; }
    .prisoner-counter-nums { font-size: 1.25rem; font-weight: 900; color: var(--fg); margin-bottom: 24px; }

    /* Social */
    .prisoner-social { display: flex; gap: 12px; margin-bottom: 24px; }
    .prisoner-social a { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(var(--fg-rgb),0.06); border: 1px solid rgba(var(--fg-rgb),0.15); transition: background 0.15s; }
    .prisoner-social a:hover { background: rgba(var(--fg-rgb),0.12); }
    .prisoner-social a svg { width: 16px; height: 16px; fill: var(--fg); }

    /* Support button */
    .prisoner-support-btn { display: inline-block; background: var(--accent); color: var(--on-accent); padding: 12px 32px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; border-radius: 24px; text-decoration: none; transition: background 0.2s; }
    .prisoner-support-btn:hover { background: var(--accent-hover); }

    /* Divider */
    .prisoner-divider { height: 1px; background: rgba(var(--fg-rgb),0.1); margin: 48px 0; }

    /* Biography */
    .prisoner-bio-title { font-size: 2.5rem; font-weight: 900; color: var(--fg); margin-bottom: 24px; }
    .prisoner-bio-content { font-size: 16px; color: rgba(var(--fg-rgb),0.75); line-height: 1.8; }
    .prisoner-bio-content p { margin-bottom: 1.25em; }

    /* Cases */
    .prisoner-cases-title { font-size: 1.5rem; font-weight: 800; color: var(--fg); margin-bottom: 16px; }
    .prisoner-case-card { border: 1px solid rgba(var(--fg-rgb),0.1); border-radius: 8px; padding: 24px; margin-bottom: 16px; }
    .prisoner-case-inst { font-size: 18px; font-weight: 700; color: var(--fg); margin-bottom: 8px; }
    .prisoner-case-meta { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .prisoner-case-field { font-size: 14px; }
    .prisoner-case-field-label { color: rgba(var(--fg-rgb),0.5); }
    .prisoner-case-field-value { color: rgba(var(--fg-rgb),0.8); }

    /* Tags */
    .prisoner-tags { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .prisoner-tag { background: rgba(var(--fg-rgb),0.06); border: 1px solid rgba(var(--fg-rgb),0.12); padding: 4px 12px; font-size: 12px; color: rgba(var(--fg-rgb),0.6); border-radius: 4px; }

    /* Rich content */
    .prose-content { font-size: 16px; color: rgba(var(--fg-rgb),0.75); line-height: 1.8; }
    .prose-content p { margin-bottom: 1.25em; }
    .prose-content a { color: var(--accent-2); text-decoration: underline; }
    .prose-content a:hover { color: var(--accent); }
    .prose-content h2 { font-size: 1.5rem; font-weight: 800; color: var(--fg); margin: 32px 0 12px; }
    .prose-content h3 { font-size: 1.25rem; font-weight: 700; color: var(--fg); margin: 24px 0 8px; }
    .prose-content ul, .prose-content ol { margin: 0 0 1.25em 1.5em; }
    .prose-content li { margin-bottom: 0.4em; }
    .prose-content blockquote { border-left: 3px solid var(--accent); padding: 12px 20px; margin: 1.5em 0; color: rgba(var(--fg-rgb),0.6); font-style: italic; }
    .prose-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 16px 0; }
    .prose-content iframe, .prose-content embed, .prose-content object { max-width: 100%; margin: 16px 0; border-radius: 8px; }
    .prose-content strong { color: var(--fg); font-weight: 700; }

    /* Related cases — square portrait grid; the name fades in over a dark
       scrim on hover. Tiles without a photo carry the name at all times on
       the same dark gradient the hero placeholder uses, so the grid stays
       legible in both themes. */
    .prisoner-related { padding: 0 0 80px; }
    .prisoner-related-title { font-size: 1.4rem; font-weight: 800; color: var(--fg); text-transform: uppercase; letter-spacing: 0.18em; margin-bottom: 12px; }
    .prisoner-related-rule { height: 1px; background: rgba(var(--fg-rgb),0.15); margin-bottom: 24px; }
    .prisoner-related-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 14px; }
    .prisoner-related-tile { position: relative; display: block; aspect-ratio: 1/1; border-radius: 4px; overflow: hidden; background: linear-gradient(135deg, #111 0%, #1a1a2e 100%); }
    .prisoner-related-tile img { width: 100%; height: 100%; object-fit: cover; display: block; filter: grayscale(45%); transition: filter 0.25s; }
    .prisoner-related-tile::after { content: ""; position: absolute; inset: 0; background: rgba(0,0,0,0.62); opacity: 0; transition: opacity 0.3s; }
    /* The name, matching the reference video: the tile darkens, then the
       name appears one word per line, left-aligned in serif caps, each word
       fading in a beat after the one above it. On mouse-out the same fade
       runs the other way round — the last word goes first, the first word
       lingers. Pure opacity: no slide, no blur, no glitch. Each word span
       carries its own in/out transition delays (--di / --do) from the
       template. */
    /* Set in the site's own face (inherited from .prisoner-page — Verlag),
       not the reference video's serif: the staggered fade is the effect
       being copied, the typeface stays the archive's. */
    .prisoner-related-name { position: absolute; inset: 0; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 10px; font-size: 13px; font-weight: 700; letter-spacing: 0.08em; line-height: 1.25; text-transform: uppercase; color: #fff; overflow-wrap: anywhere; }
    .prisoner-related-name .w { display: block; max-width: 100%; opacity: 0; transition: opacity 0.28s ease; transition-delay: var(--do, 0s); }
    .prisoner-related-tile:hover::after, .prisoner-related-tile:focus-visible::after { opacity: 1; }
    .prisoner-related-tile:hover .prisoner-related-name .w, .prisoner-related-tile:focus-visible .prisoner-related-name .w { opacity: 1; transition-delay: var(--di, 0s); }
    .prisoner-related-tile:hover img { filter: grayscale(0%); }
    /* No photo: name always shown, static, on the gradient. */
    .prisoner-related-tile.no-photo .prisoner-related-name .w { opacity: 0.85; transition: none; }
    .prisoner-related-tile.no-photo:hover .prisoner-related-name .w { opacity: 1; }
    @media (prefers-reduced-motion: reduce) {
        /* One plain fade, no stagger. The image keeps its grayscale
           treatment — that is color, not motion. */
        .prisoner-related-tile img, .prisoner-related-tile::after { transition: none; }
        .prisoner-related-name .w, .prisoner-related-tile:hover .prisoner-related-name .w, .prisoner-related-tile:focus-visible .prisoner-related-name .w { transition-delay: 0s; }
    }

    @media (max-width: 768px) {
        .prisoner-page { padding: 0 16px; }
        .prisoner-related-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
        .prisoner-related-title { font-size: 1.1rem; }
        .prisoner-hero { flex-direction: column-reverse; gap: 24px; padding: 24px 0 20px; }
        .prisoner-photo-col { flex: auto; width: 100%; }
        .prisoner-name { font-size: 2rem; }
        .prisoner-bio-title { font-size: 1.6rem; }
        .prisoner-cases-title { font-size: 1.25rem; }
        .prisoner-case-meta { grid-template-columns: 1fr; }
        .prisoner-meta-row { flex-direction: column; }
        .prisoner-meta-label { min-width: 0; margin-bottom: 2px; }
    }
</style>
@endsection

@section('body')
<div class="prisoner-page">
    {{-- Hero --}}
    <div class="prisoner-hero">
        <div class="prisoner-info">
            <h1 class="prisoner-name">{{ $prisoner->name }}</h1>
            @if($prisoner->aka)
                <div class="prisoner-aka">AKA: {{ $prisoner->aka }}</div>
            @endif

            {{-- Status badges --}}
            <div class="prisoner-status-badges">
                @if($prisoner->in_custody)<span class="prisoner-badge prisoner-badge-custody">In Custody</span>@endif
                @if($prisoner->released)<span class="prisoner-badge prisoner-badge-released">Released</span>@endif
                @if($prisoner->currently_in_exile || $prisoner->in_exile)<span class="prisoner-badge prisoner-badge-exile">In Exile</span>@endif
                @if($prisoner->awaiting_trial)<span class="prisoner-badge prisoner-badge-trial">Awaiting Trial</span>@endif
            </div>

            {{-- Meta info --}}
            <div class="prisoner-meta">
                @php
                    // Assembled from the name parts, so it carries the middle
                    // name the display name usually leaves out (Robert Francis
                    // Kennedy vs Robert F. Kennedy Jr.). Hidden when it adds
                    // nothing over the heading, so pages like Clemente Soto
                    // Velez do not print their own title twice.
                    $fullName = trim(implode(' ', array_filter([
                        $prisoner->first_name, $prisoner->middle_name, $prisoner->last_name,
                    ])));
                @endphp
                @if($fullName && $fullName !== trim($prisoner->name))
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Full name:</span><span class="prisoner-meta-value">{{ $fullName }}</span></div>
                @endif
                @if($prisoner->birthdate)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Date of birth:</span><span class="prisoner-meta-value">{{ $prisoner->formatPartialDate('birthdate', 'd. m. Y') }}</span></div>
                @endif
                @if($prisoner->death_date)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Date of death:</span><span class="prisoner-meta-value">{{ $prisoner->formatPartialDate('death_date', 'd. m. Y') }}</span></div>
                @endif
                @if($prisoner->age)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Age:</span><span class="prisoner-meta-value">{{ $prisoner->age }}@if($prisoner->death_date)<sup class="deceased-indicator">&dagger;<span class="deceased-label"> Deceased</span></sup>@endif</span></div>
                @endif
                @if($prisoner->gender)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Gender:</span><span class="prisoner-meta-value">{{ $prisoner->gender }}</span></div>
                @endif
                @if($prisoner->race)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Race:</span><span class="prisoner-meta-value">{{ $prisoner->race }}</span></div>
                @endif
                @if($prisoner->era)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Era:</span><span class="prisoner-meta-value">{{ $prisoner->era }}</span></div>
                @endif
                @if($prisoner->inmate_number)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Inmate Number:</span><span class="prisoner-meta-value">#{{ $prisoner->inmate_number }}</span></div>
                @endif
                @if($prisoner->state)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">State:</span><span class="prisoner-meta-value">{{ $prisoner->state }}</span></div>
                @endif
                @if($prisoner->address)
                    <div class="prisoner-meta-row"><span class="prisoner-meta-label">Address:</span><span class="prisoner-meta-value">{{ $prisoner->address }}</span></div>
                @endif
            </div>

            {{-- Imprisonment + exile counters (rendered separately when both apply) --}}
            @php
                use App\Support\ExileDuration;
                use App\Support\ImprisonmentDuration;

                $imprisonment = ImprisonmentDuration::summarize($prisoner->cases);
                $totalDays = $imprisonment['days'];

                // Cases may describe overlapping custody or exile periods.
                $totalExileDays = ExileDuration::totalDays($prisoner->cases);

                // Anchor each counter to the real start date so the calendar
                // diff is accurate (e.g. May 13 → Dec 13 reads as a clean 7
                // months, not "7 months 4 days").
                $imprisonStart = $imprisonment['start'];
                $exileStart = ExileDuration::startFor($prisoner->cases);

                // Months a source documented directly, where they beat the
                // date arithmetic. Null for the ordinary record.
                $servedMonths = $imprisonment['months'];

                $renderCounter = function ($start, int $totalDays, string $label, ?int $months = null) {
                    if ($totalDays <= 0) return '';
                    return '<div class="prisoner-counter-label">' . e($label) . ':</div>' .
                           '<div class="prisoner-counter-nums">' . e(ImprisonmentDuration::phrase($start, $totalDays, $months)) . '</div>';
                };
            @endphp
            @if($totalDays > 0)
                {!! $renderCounter($imprisonStart, $totalDays, 'Time Imprisoned', $servedMonths) !!}
            @endif
            @if($totalExileDays > 0)
                {!! $renderCounter($exileStart, $totalExileDays, 'Time in Exile') !!}
            @endif

            {{-- Social links --}}
            @if($prisoner->website || $prisoner->twitter || $prisoner->facebook || $prisoner->instagram)
                <div class="prisoner-social">
                    @if($prisoner->website)<a href="{{ $prisoner->website }}" target="_blank" title="Website"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg></a>@endif
                    @if($prisoner->twitter)<a href="{{ $prisoner->twitter }}" target="_blank" title="Twitter"><svg viewBox="0 0 24 24"><path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/></svg></a>@endif
                    @if($prisoner->facebook)<a href="{{ $prisoner->facebook }}" target="_blank" title="Facebook"><svg viewBox="0 0 24 24"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/></svg></a>@endif
                    @if($prisoner->instagram)<a href="{{ $prisoner->instagram }}" target="_blank" title="Instagram"><svg viewBox="0 0 24 24"><path d="M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z"/></svg></a>@endif
                </div>
            @endif

            {{-- Support button --}}
            <a href="/prisoner-outreach" class="prisoner-support-btn">Support</a>

            {{-- Ideologies & Affiliation tags --}}
            @if(!empty($prisoner->ideologies) || !empty($prisoner->affiliation))
                <div class="prisoner-tags">
                    @foreach($prisoner->ideologies ?? [] as $ideology)
                        <span class="prisoner-tag">{{ $ideology }}</span>
                    @endforeach
                    @foreach($prisoner->affiliation ?? [] as $aff)
                        <span class="prisoner-tag">{{ $aff }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Photo --}}
        <div class="prisoner-photo-col">
            @if($prisoner->photo)
                <div class="prisoner-photo">
                    <img src="{{ $prisoner->photoUrl() }}" alt="{{ $prisoner->name }}" decoding="async">
                </div>
            @else
                <div class="prisoner-photo-placeholder">
                    <img src="/images/no-image-available.svg" alt="No image available" style="width:70%; height:auto; opacity:0.8;">
                </div>
            @endif
        </div>
    </div>

    <div class="prisoner-divider"></div>

    {{-- Biography --}}
    @if($prisoner->description)
        <div style="max-width: 800px; padding-bottom: 48px;">
            <h2 class="prisoner-bio-title">Biography</h2>
            <div class="prisoner-bio-content">
                @foreach(explode("\n", $prisoner->description) as $para)
                    @if(trim($para))
                        <p>{{ $para }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Page Content (rich text body) --}}
    @if($prisoner->body)
        <div style="max-width: 800px; padding-bottom: 48px;">
            <div class="prisoner-bio-content prose-content">
                {!! $prisoner->body !!}
            </div>
        </div>
        <div class="prisoner-divider"></div>
    @endif

    {{-- Podcast Episodes --}}
    @php $episodes = \App\Models\PodcastEpisode::published()->where('prisoner_id', $prisoner->id)->orderBy('sort_order')->get(); @endphp
    @if($episodes->isNotEmpty())
        <div class="prisoner-divider"></div>
        <div style="max-width: 800px;">
            <h2 class="prisoner-cases-title">Related Episodes</h2>
            @include('sections.podcast-player', ['episodes' => $episodes])
        </div>
    @endif

    {{-- Cases --}}
    @if($prisoner->cases->isNotEmpty())
        <div class="prisoner-divider"></div>
        <div style="padding-bottom: 80px;">
            <h2 class="prisoner-cases-title">Case{{ $prisoner->cases->count() > 1 ? 's' : '' }}</h2>
            @foreach($prisoner->cases as $case)
                <div class="prisoner-case-card">
                    @if($case->institution)
                        <div class="prisoner-case-inst">{{ $case->institution->name }}</div>
                    @endif
                    <div class="prisoner-case-meta">
                        @if($case->charges)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Charges: </span><span class="prisoner-case-field-value">{{ $case->charges }}</span></div>@endif
                        @if($case->arrest_date)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Arrested: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('arrest_date') }}</span></div>@endif
                        @if($case->sentenced_date)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Sentenced: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('sentenced_date') }}</span></div>@endif
                        @if($case->incarceration_date)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Incarcerated: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('incarceration_date') }}</span></div>@endif
                        @if($case->release_date)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Released: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('release_date') }}</span></div>@endif
                        @if($case->in_exile_since)<div class="prisoner-case-field"><span class="prisoner-case-field-label">In exile since: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('in_exile_since') }}</span></div>@endif
                        @if($case->end_of_exile)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Exile ended: </span><span class="prisoner-case-field-value">{{ $case->formatPartialDate('end_of_exile') }}</span></div>@endif
                        @if($case->sentence)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Sentence: </span><span class="prisoner-case-field-value">{{ $case->sentence }}</span></div>@endif
                        @if($case->prosecutor)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Prosecutor: </span><span class="prisoner-case-field-value">{{ $case->prosecutor }}</span></div>@endif
                        @if($case->judge)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Judge: </span><span class="prisoner-case-field-value">{{ $case->judge }}</span></div>@endif
                        @if($case->indicted)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Indicted: </span><span class="prisoner-case-field-value">{{ $case->indicted }}</span></div>@endif
                        @if($case->convicted)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Convicted: </span><span class="prisoner-case-field-value">{{ $case->convicted }}</span></div>@endif
                        @if($case->plead)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Plead: </span><span class="prisoner-case-field-value">{{ $case->plead }}</span></div>@endif
                        @if($case->institution && $case->institution->state)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Institution State: </span><span class="prisoner-case-field-value">{{ $case->institution->state }}</span></div>@endif
                        @if($case->institution && $case->institution->security)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Security: </span><span class="prisoner-case-field-value">{{ $case->institution->security }}</span></div>@endif
                        @if($case->institution && $case->institution->mailing_address)<div class="prisoner-case-field"><span class="prisoner-case-field-label">Mailing Address: </span><span class="prisoner-case-field-value">{{ $case->institution->mailing_address }}</span></div>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Related cases — people who share this record's organization, trial
         institution or era. Computed and thresholded in the controller;
         hidden entirely when nothing clears the bar, rather than padded
         with near-strangers. --}}
    @if(($related ?? collect())->isNotEmpty())
        <div class="prisoner-related">
            <h2 class="prisoner-related-title">Related Cases</h2>
            <div class="prisoner-related-rule"></div>
            <div class="prisoner-related-grid">
                @foreach($related as $rel)
                    <a href="/prisoner/{{ $rel->slug ?: $rel->id }}" class="prisoner-related-tile {{ $rel->photo ? '' : 'no-photo' }}" aria-label="{{ $rel->name }}">
                        @if($rel->photo)
                            <img src="{{ $rel->photoUrl() }}" alt="{{ $rel->name }}" loading="lazy" decoding="async">
                        @endif
                        {{-- One word per line, each with its own stagger: --di
                             delays the fade-in top-down, --do delays the
                             fade-out bottom-up, so the first word is first in
                             and last out, as in the reference video. --}}
                        @php $words = preg_split('/\s+/', trim($rel->name)) ?: [$rel->name]; $n = count($words); @endphp
                        <span class="prisoner-related-name">
                            @foreach($words as $i => $w)
                                <span class="w" style="--di:{{ number_format($i * 0.12, 2) }}s;--do:{{ number_format(($n - 1 - $i) * 0.08, 2) }}s">{{ $w }}</span>
                            @endforeach
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
