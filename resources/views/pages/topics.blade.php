@extends('app')

@section('title', 'Topics | NPPC')

@section('head')
{{-- Fonts matching ecfr.eu "Mapping Palestinian Politics": Karla (body/nav/
     headings) + Playfair Display (serif display accent). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Karla:ital,wght@0,400;0,700;1,400&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<style>
    /* Full-bleed: break out of the global .container max-width (like the tracker). */
    body.page-topics main.container, body.page-topics .container { width: 100% !important; max-width: none !important; padding-left: 0 !important; padding-right: 0 !important; overflow: visible !important; }
    body.page-topics { background: #14110e; }

    /* Typography — Karla throughout (like ecfr.eu's body + h1–h6), with
       Playfair Display as the serif accent on the topic title. Form controls
       don't inherit font-family, so they're named explicitly. */
    .tpx, .tpx input, .tpx button, .tpx textarea, .tpx select { font-family: "Karla", Helvetica, Arial, sans-serif; }
    /* The right-hand detail panel keeps the site's original default sans — the
       Karla/Playfair fonts apply only to the explorer nav. */
    .tpx-detail, .tpx-detail input, .tpx-detail button, .tpx-detail textarea, .tpx-detail select { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }

    /* ── Topic explorer, modeled on ecfr.eu "Mapping Palestinian Politics":
       a strong photographic backdrop with light nav columns on the left, and
       a white detail panel with a large image on the right. ── */
    /* Break out to the full viewport width so the photo spans the page edge
       to edge, regardless of the centered .container around it. */
    .tpx { position: relative; min-height: calc(100vh - 108px); width: 100vw; margin-left: calc(50% - 50vw); }

    /* Photographic backdrop spanning the navigation area */
    .tpx-photo { position: absolute; inset: 0; z-index: 0; background-size: cover; background-position: center; }
    .tpx-photo-tint { position: absolute; inset: 0; z-index: 1; background: linear-gradient(90deg, rgba(8,7,5,0.82) 0%, rgba(8,7,5,0.45) 40%, rgba(8,7,5,0.55) 100%); }

    .tpx-grid { position: relative; z-index: 2; display: grid; grid-template-columns: minmax(200px, 240px) minmax(220px, 1fr) minmax(380px, 520px); grid-template-rows: auto 1fr; align-items: stretch; height: calc(100vh - 108px); }

    /* Thin translucent scrollbars for the nav columns over the photo. The
       thumb stays invisible until the cursor is over that column, then fades
       in — so an idle scrollbar never sits over the photo. */
    .tpx-nav, .tpx-sub-col { scrollbar-width: thin; scrollbar-color: transparent transparent; transition: scrollbar-color 0.3s ease; }
    .tpx-nav:hover, .tpx-sub-col:hover { scrollbar-color: rgba(255,255,255,0.3) transparent; }
    .tpx-nav::-webkit-scrollbar, .tpx-sub-col::-webkit-scrollbar { width: 8px; }
    .tpx-nav::-webkit-scrollbar-thumb, .tpx-sub-col::-webkit-scrollbar-thumb { background: transparent; border-radius: 4px; transition: background 0.3s ease; }
    .tpx-nav:hover::-webkit-scrollbar-thumb, .tpx-sub-col:hover::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); }
    .tpx-nav::-webkit-scrollbar-thumb:hover, .tpx-sub-col::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.45); }

    /* Header bar sits across the nav area */
    .tpx-head { grid-column: 1 / 3; display: flex; align-items: center; justify-content: flex-end; gap: 24px; padding: 28px clamp(20px, 3vw, 40px) 0; }
    .tpx-actions { display: flex; gap: 20px; }
    .tpx-action { display: inline-flex; align-items: center; gap: 7px; background: none; border: 0; cursor: pointer; font: inherit; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.15s; }
    .tpx-action:hover { color: var(--on-dark); }
    .tpx-action svg { width: 15px; height: 15px; }

    /* Left column — root topics + search, over the photo */
    .tpx-nav { grid-column: 1; padding: 26px clamp(20px, 3vw, 40px); overflow-y: auto; overflow-x: hidden; min-height: 0; }
    .tpx-nav-item { display: block; font-size: 15px; font-weight: 600; color: rgba(255,255,255,0.78); padding: 9px 0; text-decoration: none; transition: color 0.15s; text-shadow: 0 1px 8px rgba(0,0,0,0.6); }
    .tpx-nav-item:hover { color: var(--on-dark); }
    .tpx-nav-item.active { color: #8b93ff; }
    .tpx-search-wrap { margin-top: 24px; }
    .tpx-search { background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.3); color: var(--on-dark); padding: 9px 12px; font-size: 13px; width: 100%; outline: none; }
    .tpx-search::placeholder { color: rgba(255,255,255,0.5); }
    .tpx-search:focus { border-color: rgba(255,255,255,0.7); }
    /* Search results — in-flow below the box so the column scrolls naturally. */
    .tpx-search-results { margin-top: 6px; border: 1px solid rgba(255,255,255,0.25); background: rgba(0,0,0,0.45); max-height: 300px; overflow-y: auto; }
    .tpx-search-results[hidden] { display: none; }
    a.tpx-search-result { display: block; padding: 8px 12px; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.08); }
    a.tpx-search-result:last-child { border-bottom: 0; }
    a.tpx-search-result:hover, a.tpx-search-result.sel { background: rgba(255,255,255,0.14); }
    .tpx-search-result .tsr-t { display: block; color: var(--on-dark); font-size: 13px; line-height: 1.35; }
    .tpx-search-result .tsr-p { display: block; color: rgba(255,255,255,0.55); font-size: 11px; margin-top: 2px; }
    .tpx-search-empty { padding: 8px 12px; color: rgba(255,255,255,0.55); font-size: 12px; }

    /* Middle column — sub-topics, over the photo. When the active sub-topic has
       nested topics, a second list sits beside the sub-topics inside this same
       grid column, filling space that was already empty — so showing it never
       moves the left nav, the sub-topics list, or the detail panel. */
    .tpx-sub { grid-column: 2; padding: 26px clamp(20px, 3vw, 40px); border-left: 1px solid rgba(255,255,255,0.18); overflow: hidden; min-height: 0; }
    .tpx-sub-inner { display: flex; align-items: stretch; flex-wrap: wrap; height: 100%; min-height: 0; }
    /* Each sub-topic column scrolls on its own when its list is taller than the
       viewport (like the ecfr.eu reference), and animates independently so an
       unchanged column never re-renders when only a neighbour changes. */
    .tpx-sub-col { flex: 0 0 auto; min-width: 150px; min-height: 0; max-height: 100%; overflow-y: auto; overflow-x: hidden; transition: opacity 0.5s linear, transform 0.5s ease; }
    /* Enter state for soft-nav: a (re)built column slides in from the right and
       fades (mirrors the ecfr.eu "Mapping Palestinian Politics" transition). */
    .tpx-sub-col.tpx-enter { opacity: 0; transform: translateX(32px); }
    /* Nested-topics list — sits to the right of the sub-topics list */
    .tpx-sub2 { margin-left: clamp(20px, 2.2vw, 34px); padding-left: clamp(22px, 2.4vw, 40px); border-left: 1px solid rgba(255,255,255,0.18); }
    .tpx-sub-heading { font-size: 14px; font-weight: 800; letter-spacing: 0.03em; color: var(--on-dark); margin: 0 0 18px; text-shadow: 0 1px 8px rgba(0,0,0,0.6); }
    .tpx-sub-link { display: block; font-size: 14px; line-height: 1.4; color: rgba(255,255,255,0.82); padding: 8px 0; text-decoration: none; transition: color 0.15s; text-shadow: 0 1px 8px rgba(0,0,0,0.6); }
    .tpx-sub-link:hover { color: var(--on-dark); }
    .tpx-sub-link.active { color: #8b93ff; }

    /* Right column — white detail panel with a large image */
    .tpx-detail { grid-column: 3; grid-row: 1 / span 2; position: relative; z-index: 3; background: #fff; color: #1a1a1a; padding: 40px clamp(28px, 3vw, 48px); overflow-y: auto; max-height: calc(100vh - 108px); transition: opacity 0.5s linear; }
    .tpx-detail-eyebrow { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #6b7280; margin-bottom: 18px; }
    .tpx-detail-hero { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; display: block; margin-bottom: 22px; background: #ece9e4; }
    .tpx-detail-body { font-size: 16px; color: #333; line-height: 1.75; }
    .tpx-detail-body p { margin-bottom: 1.2em; }
    .tpx-detail-body a { color: #1f3df0; }
    .tpx-detail-empty { font-size: 16px; color: #9aa0a6; font-style: italic; }
    /* Contribute-to-the-database panel */
    .tpx-contribute-thanks { background: #eafaf1; border: 1px solid #46c08d; color: #176b48; border-radius: 8px; padding: 12px 14px; font-size: 14px; margin-bottom: 18px; }
    .tpx-contribute { display: flex; flex-direction: column; gap: 14px; margin-top: 18px; }
    .tpx-cf { display: flex; flex-direction: column; gap: 6px; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; color: #6b7280; }
    .tpx-cf input, .tpx-cf select, .tpx-cf textarea { font: inherit; font-weight: 400; letter-spacing: normal; text-transform: none; color: #1a1a1a; background: #fff; border: 1px solid #d6d3cd; border-radius: 7px; padding: 9px 11px; width: 100%; }
    .tpx-cf textarea { resize: vertical; }
    .tpx-cf input:focus, .tpx-cf select:focus, .tpx-cf textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(86,96,254,0.15); }
    .tpx-cf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .tpx-contribute-btn { align-self: flex-start; font: inherit; font-weight: 700; background: var(--accent); color: var(--on-accent); border: 0; border-radius: 8px; padding: 11px 22px; cursor: pointer; transition: background 0.15s ease; }
    .tpx-contribute-btn:hover { background: var(--accent-hover); }
    @@media (max-width: 560px) { .tpx-cf-row { grid-template-columns: 1fr; } }

    /* Index — alphabetical list of all sub-topics */
    .tpx-index-letter { font-size: 13px; font-weight: 800; letter-spacing: 0.08em; color: #6b7280; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 6px; margin: 26px 0 8px; }
    .tpx-index-letter:first-of-type { margin-top: 0; }
    .tpx-index-link { display: block; font-size: 15px; color: #1a1a1a; text-decoration: none; padding: 6px 0; }
    .tpx-index-link:hover { color: #1f3df0; }

    .tpx-cases-title { font-size: 16px; font-weight: 800; color: #111; margin: 30px 0 12px; border-top: 1px solid rgba(0,0,0,0.1); padding-top: 22px; }
    .tpx-case { display: flex; gap: 12px; align-items: center; padding: 11px 0; border-bottom: 1px solid rgba(0,0,0,0.08); }
    .tpx-case-photo { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; flex: 0 0 auto; background: #e7e3dd; }
    .tpx-case-name { font-size: 14px; font-weight: 700; }
    .tpx-case-name a { color: #111; text-decoration: none; }
    .tpx-case-name a:hover { color: #1f3df0; }
    .tpx-case-meta { font-size: 12px; color: #8a8f98; margin-top: 1px; }

    @@media (max-width: 1024px) {
        /* Stack and let the page scroll normally instead of scrolling columns. */
        .tpx-grid { grid-template-columns: 1fr 1fr; grid-template-rows: auto; height: auto; }
        .tpx-head { grid-column: 1 / 3; }
        .tpx-nav, .tpx-sub { overflow: visible; }
        .tpx-sub-inner { height: auto; }
        /* flex: 0 0 auto sizes each column to its longest unwrapped title
           (e.g. "The Haymarket Affair & the Anti-Anarchist Era (1886–1901)"
           ≈ 482px). On desktop .tpx-sub's overflow: hidden contains that;
           this media block removes the clip for natural page scroll, so the
           column must also become shrinkable or it widens the phone's layout
           viewport past the screen. */
        .tpx-sub-col { overflow: visible; max-height: none; flex-shrink: 1; min-width: 0; max-width: 100%; }
        .tpx-detail { grid-column: 1 / 3; grid-row: auto; max-height: none; }
    }
    @@media (max-width: 600px) {
        .tpx-grid { grid-template-columns: 1fr; }
        .tpx-head { grid-column: 1; flex-direction: column; align-items: flex-start; gap: 14px; }
        .tpx-nav, .tpx-sub, .tpx-detail { grid-column: 1; }
        .tpx-sub { border-left: 0; }
        .tpx-sub2 { margin-left: 0; padding-left: 0; border-left: 0; }
    }
</style>
@endsection

@section('body')
@php
    $activeGrandchild = $activeGrandchild ?? null;
    $displayTopic = $activeGrandchild ?: ($activeChild ?: $activeTopic);
    // Nav lists show only published topics — an unpublished topic in the nav
    // wouldn't resolve when clicked (the controller looks it up published-only).
    $subChildren = $activeTopic ? $activeTopic->children->where('published', true) : collect();
    $nestedChildren = $activeChild ? $activeChild->children->where('published', true) : collect();
    // A third nav column appears when the active sub-topic has nested topics.
    $showL3 = $nestedChildren->isNotEmpty();

    // Bundled fallback imagery per topic, used only when a topic has no
    // image of its own. Any image uploaded in admin overrides these.
    $topicDefaults = [
        // Sections (drive the full-bleed background)
        'introduction'          => '/images/freedom.jpg',
        'movements'             => '/images/topics-movements.jpg',
        'eras'                  => '/images/topics-eras.jpg',
        'organizations'         => '/images/topics-organizations.jpg',
        // Movement leaves (drive the detail-panel hero)
        'black-lives-matter'    => '/images/section_1.jpg',
        'environmental-justice' => '/images/volunteer.jpg',
        'anti-war-activism'     => '/images/section_2.jpg',
    ];
    $defaultFor = function ($topic) use ($topicDefaults) {
        if (! $topic) return '/images/prison-hell.jpg';
        if (isset($topicDefaults[$topic->slug])) return $topicDefaults[$topic->slug];
        // A leaf with no image of its own inherits its section's image.
        $parent = $topic->parent_id ? $topic->parent : null;
        if ($parent && isset($topicDefaults[$parent->slug])) return $topicDefaults[$parent->slug];
        return '/images/prison-hell.jpg';
    };

    // Per-topic full-bleed background overrides, keyed by the active leaf
    // topic's slug. Lets a specific sub-topic carry an explicit backdrop URL.
    // (Topics with their own uploaded image no longer need an override — the
    // image itself now drives the full-bleed background, below.)
    $bgOverrides = [];
    $activeOverride = ($displayTopic && isset($bgOverrides[$displayTopic->slug]))
        ? $bgOverrides[$displayTopic->slug]
        : null;

    // Full-bleed background: prefer the *displayed* topic's own image, so a
    // selected sub-topic's photo becomes the full-width backdrop (not just the
    // detail-panel hero). Falls back to the section's image, then a bundled
    // default.
    $bgImage = $showIndex
        ? '/images/topics-index.jpg'
        : ($showContribute
            ? '/images/topics-contributions.jpg'
            : ($activeOverride
                ?: ($displayTopic && $displayTopic->image
                    ? Storage::url($displayTopic->image)
                    : ($activeChild && $activeChild->image
                        ? Storage::url($activeChild->image)
                        : ($activeTopic && $activeTopic->image
                            ? Storage::url($activeTopic->image)
                            : $defaultFor($displayTopic ?: $activeTopic))))));
    $heroImage = $activeOverride
        ?: ($displayTopic && $displayTopic->image
            ? Storage::url($displayTopic->image)
            : $defaultFor($displayTopic));

    // Only show the detail-panel hero when it's a different image from the
    // full-bleed backdrop. A topic with no image of its own falls back to its
    // section's image, which is also what drives the background — so without
    // this guard the same photo appears twice (once behind, once in the panel).
    $showHero = $heroImage && $heroImage !== $bgImage;
@endphp
<div class="tpx">
    {{-- Photographic backdrop --}}
    <div class="tpx-photo" style="background-image: url('{{ $bgImage }}');"></div>
    <div class="tpx-photo-tint"></div>

    <div class="tpx-grid">
        {{-- Header (spans the nav columns) --}}
        <div class="tpx-head">
            <div class="tpx-actions">
                <button type="button" class="tpx-action" onclick="window.print()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                    Print
                </button>
                <button type="button" class="tpx-action" onclick="tpxShare()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>
                    Share
                </button>
            </div>
        </div>

        {{-- Column 1: root topics --}}
        <div class="tpx-nav">
            @foreach($rootTopics as $topic)
                <a href="/topics/{{ $topic->slug }}" data-no-fade
                   class="tpx-nav-item {{ $activeTopic && $activeTopic->id === $topic->id ? 'active' : '' }}">
                    {{ $topic->title }}
                </a>
            @endforeach
            <a href="/topics/index" data-no-fade class="tpx-nav-item {{ $showIndex ? 'active' : '' }}">Index</a>
            <a href="/topics/contributions" data-no-fade class="tpx-nav-item {{ $showContribute ? 'active' : '' }}">Contributions</a>
            <div class="tpx-search-wrap">
                <input type="text" class="tpx-search" placeholder="Search..." id="topic-search" autocomplete="off" spellcheck="false">
                <div class="tpx-search-results" id="topic-search-results" hidden></div>
            </div>
        </div>

        {{-- Column 2: sub-topics, with an optional nested-topics list beside them.
             The nested list lives inside this same grid column, so it fills space
             that was already empty and never shifts the other columns. --}}
        <div class="tpx-sub">
            <div class="tpx-sub-inner">
                <div class="tpx-sub-col">
                    @if($activeTopic && $subChildren->isNotEmpty())
                        <div class="tpx-sub-heading">About {{ $activeTopic->title }}</div>
                        @foreach($subChildren as $child)
                            <a href="/topics/{{ $child->slug }}" data-no-fade
                               class="tpx-sub-link {{ $activeChild && $activeChild->id === $child->id ? 'active' : '' }}">
                                {{ $child->title }}
                            </a>
                        @endforeach
                    @endif
                </div>
                @if($showL3)
                <div class="tpx-sub-col tpx-sub2">
                    <div class="tpx-sub-heading">About {{ $activeChild->title }}</div>
                    @foreach($nestedChildren as $grandchild)
                        <a href="/topics/{{ $grandchild->slug }}" data-no-fade
                           class="tpx-sub-link {{ $activeGrandchild && $activeGrandchild->id === $grandchild->id ? 'active' : '' }}">
                            {{ $grandchild->title }}
                        </a>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Detail panel --}}
        <div class="tpx-detail">
            @if($showContribute)
                <div class="tpx-detail-eyebrow">Contribute to the database</div>
                @if(request('form_submitted'))
                    <div class="tpx-contribute-thanks">Thank you — your contribution has been received. Our research team reviews every submission before it goes live.</div>
                @endif
                <div class="tpx-detail-body">
                    <p>This database is built and corrected by people like you. Know of a political prisoner we're missing, a detail that's wrong, or a source we should cite? Send it here — researchers verify every submission before it's published.</p>
                </div>
                <form class="tpx-contribute" method="POST" action="/form/contribution">
                    @csrf
                    <label class="tpx-cf">Type of contribution
                        <select name="contribution_type">
                            <option>New case / prisoner</option>
                            <option>Correction to an existing case</option>
                            <option>Additional source or document</option>
                            <option>Other</option>
                        </select>
                    </label>
                    <label class="tpx-cf">Person or case this concerns
                        <input type="text" name="subject_name" placeholder="Name of the prisoner or case">
                    </label>
                    <label class="tpx-cf">Details
                        <textarea name="details" rows="5" required placeholder="What should we add, change, or correct?"></textarea>
                    </label>
                    <label class="tpx-cf">Sources / links
                        <textarea name="sources" rows="2" placeholder="Court records, news articles, organization reports…"></textarea>
                    </label>
                    <div class="tpx-cf-row">
                        <label class="tpx-cf">Your name
                            <input type="text" name="contributor_name">
                        </label>
                        <label class="tpx-cf">Your email
                            <input type="email" name="contributor_email" placeholder="So we can follow up">
                        </label>
                    </div>
                    <div class="g-recaptcha" data-sitekey="6LdREZkqAAAAADv7Ei5dS_SZ1oVaz6A5FE7nacrw"></div>
                    <button type="submit" class="tpx-contribute-btn">Submit contribution</button>
                </form>
            @elseif($showIndex)
                <div class="tpx-detail-eyebrow">Index — All Topics A–Z</div>
                @forelse($indexGroups as $letter => $topics)
                    <div class="tpx-index-letter">{{ $letter }}</div>
                    @foreach($topics as $t)
                        <a class="tpx-index-link" data-no-fade href="/topics/{{ $t->slug }}">{{ $t->title }}</a>
                    @endforeach
                @empty
                    <div class="tpx-detail-empty">No topics to index yet.</div>
                @endforelse
            @elseif($displayTopic)
                <div class="tpx-detail-eyebrow">{{ strtoupper($displayTopic->title) }}</div>

                @if($displayTopic->slug === 'black-panther-party')
                    <p style="margin-bottom: 22px;"><a href="/black-panther-party" style="color: #4338ca; font-weight: 700;">Explore the Black Panther Party: places, programs &amp; people &rarr;</a></p>
                @endif

                @if($showHero)
                    <img class="tpx-detail-hero" src="{{ $heroImage }}" alt="{{ $displayTopic->title }}">
                @endif

                @if($displayTopic->body)
                    <div class="tpx-detail-body">{!! $displayTopic->body !!}</div>
                @else
                    <div class="tpx-detail-empty">Content for this topic is coming soon.</div>
                @endif

                @if($relatedPrisoners->isNotEmpty())
                    <div class="tpx-cases-title">Related Cases ({{ $relatedPrisoners->count() }})</div>
                    @foreach($relatedPrisoners as $prisoner)
                        <div class="tpx-case">
                            @if($prisoner->photo)
                                <img src="{{ $prisoner->photoUrl() }}" class="tpx-case-photo" alt="">
                            @else
                                <div class="tpx-case-photo"></div>
                            @endif
                            <div>
                                <div class="tpx-case-name"><a href="{{ $prisoner->url }}">{{ $prisoner->name }}</a></div>
                                <div class="tpx-case-meta">{{ $prisoner->era }}{{ $prisoner->era && $prisoner->state ? ' · ' : '' }}{{ $prisoner->state }}</div>
                            </div>
                        </div>
                    @endforeach
                @endif
            @else
                <div class="tpx-detail-empty">Select a topic from the left to explore.</div>
            @endif
        </div>
    </div>
</div>

<script>window.__TPX_INDEX = @json($searchIndex);</script>
<script>
/* Topic search — searches every published topic (title + section path) and
   shows clickable results, like the ecfr.eu mapping explorer's search. */
(function () {
    var IDX = window.__TPX_INDEX || [];
    var input = document.getElementById('topic-search');
    var box = document.getElementById('topic-search-results');
    if (!input || !box) return;
    var sel = -1;

    function clearResults() { box.innerHTML = ''; box.hidden = true; sel = -1; }

    function render(matches) {
        box.innerHTML = '';
        sel = -1;
        if (!matches.length) {
            var empty = document.createElement('div');
            empty.className = 'tpx-search-empty';
            empty.textContent = 'No matching topics';
            box.appendChild(empty);
        }
        matches.slice(0, 12).forEach(function (m) {
            var a = document.createElement('a');
            a.className = 'tpx-search-result';
            a.href = '/topics/' + m.s;
            var t = document.createElement('span');
            t.className = 'tsr-t';
            t.textContent = m.t;
            a.appendChild(t);
            if (m.p) {
                var p = document.createElement('span');
                p.className = 'tsr-p';
                p.textContent = m.p;
                a.appendChild(p);
            }
            box.appendChild(a);
        });
        box.hidden = false;
    }

    function search(q) {
        q = q.trim().toLowerCase();
        if (q.length < 2) { clearResults(); return; }
        var scored = [];
        for (var i = 0; i < IDX.length; i++) {
            var t = IDX[i].t.toLowerCase(), p = (IDX[i].p || '').toLowerCase();
            var s = -1;
            if (t.indexOf(q) === 0) s = 0;                       // title starts with
            else if (t.indexOf(' ' + q) !== -1) s = 1;           // word in title
            else if (t.indexOf(q) !== -1) s = 2;                 // anywhere in title
            else if (p.indexOf(q) !== -1) s = 3;                 // in section path
            if (s >= 0) scored.push([s, IDX[i]]);
        }
        scored.sort(function (a, b) { return a[0] - b[0] || a[1].t.localeCompare(b[1].t); });
        render(scored.map(function (x) { return x[1]; }));
    }

    function items() { return box.querySelectorAll('a.tpx-search-result'); }
    function highlight(n) {
        var list = items();
        if (!list.length) return;
        sel = (n + list.length) % list.length;
        list.forEach(function (el, i) { el.classList.toggle('sel', i === sel); });
        list[sel].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('input', function () { search(this.value); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { clearResults(); input.value = ''; input.blur(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); highlight(sel + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(sel - 1); }
        else if (e.key === 'Enter') {
            var list = items();
            var target = list[sel >= 0 ? sel : 0];
            if (target) { e.preventDefault(); target.click(); }
        }
    });
    // Choosing a result closes the panel (the click itself soft-navigates).
    box.addEventListener('click', function (e) {
        if (e.target.closest('a.tpx-search-result')) {
            window.setTimeout(function () { clearResults(); input.value = ''; }, 0);
        }
    });
    // Click elsewhere closes the panel.
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.tpx-search-wrap')) clearResults();
    });
})();
function tpxShare() {
    var url = window.location.href, title = document.title;
    if (navigator.share) { navigator.share({ title: title, url: url }).catch(function () {}); }
    else if (navigator.clipboard) { navigator.clipboard.writeText(url).then(function () { alert('Link copied to clipboard'); }); }
    else { window.prompt('Copy this link:', url); }
}

/* Soft navigation between topics: swap the explorer in place instead of a
   full page reload. The far-right detail panel fades out and back in, and the
   backdrop photo crossfades. Degrades to normal navigation. */
(function () {
    function bgImageOf(el) { return el ? el.style.backgroundImage : ''; }

    // Structural signature of a single nav column: its heading text plus the
    // ordered list of link targets. Ignores which item is active (and markup
    // noise), so navigating within a column is not a change to that column.
    function colSignature(colEl) {
        if (!colEl) return null;
        var parts = [];
        colEl.querySelectorAll('.tpx-sub-heading, a.tpx-sub-link').forEach(function (n) {
            parts.push(n.classList.contains('tpx-sub-heading')
                ? 'H:' + (n.textContent || '').trim()
                : 'L:' + (n.getAttribute('href') || ''));
        });
        return parts.join('|');
    }

    // Move the active highlight inside a column to match the fresh version,
    // without rebuilding the column's DOM.
    function syncActive(scope, freshScope, sel) {
        var active = {};
        freshScope.querySelectorAll(sel + '.active').forEach(function (a) {
            active[a.getAttribute('href')] = true;
        });
        scope.querySelectorAll(sel).forEach(function (a) {
            a.classList.toggle('active', !!active[a.getAttribute('href')]);
        });
    }

    // Slide + fade a (re)built column in from the right (the ecfr.eu transition).
    function animateCol(colEl) {
        colEl.classList.add('tpx-enter');
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { colEl.classList.remove('tpx-enter'); });
        });
    }

    // Reconcile the sub-topic columns one by one: keep an unchanged column's DOM
    // (just move its highlight), rebuild a changed one, insert a new one, remove
    // a gone one — so a column never re-renders unless it actually changed.
    function reconcileColumns(curInner, freshInner) {
        if (!curInner || !freshInner) return;
        var kids = function (el) {
            return Array.prototype.filter.call(el.children, function (c) {
                return c.classList && c.classList.contains('tpx-sub-col');
            });
        };
        var curCols = kids(curInner), freshCols = kids(freshInner);
        var n = Math.max(curCols.length, freshCols.length);
        for (var i = 0; i < n; i++) {
            var curCol = curCols[i], freshCol = freshCols[i];
            if (curCol && freshCol) {
                if (colSignature(curCol) === colSignature(freshCol)) {
                    syncActive(curCol, freshCol, 'a.tpx-sub-link');
                } else {
                    curCol.className = freshCol.className;
                    curCol.innerHTML = freshCol.innerHTML;
                    curCol.scrollTop = 0;
                    animateCol(curCol);
                }
            } else if (freshCol && !curCol) {
                var clone = document.importNode(freshCol, true);
                curInner.appendChild(clone);
                animateCol(clone);
            } else if (curCol && !freshCol) {
                curCol.parentNode.removeChild(curCol);
            }
        }
    }

    // Crossfade the backdrop: layer the new photo above the current one at
    // opacity 0, fade it in, then drop any older layers.
    function crossfadeBackground(tpx, newBg) {
        var anchor = tpx.querySelector('.tpx-photo-tint') || tpx.querySelector('.tpx-grid');
        var layer = document.createElement('div');
        layer.className = 'tpx-photo';
        layer.style.backgroundImage = newBg;
        layer.style.opacity = '0';
        layer.style.transition = 'opacity 0.5s linear';
        if (anchor) { tpx.insertBefore(layer, anchor); } else { tpx.appendChild(layer); }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { layer.style.opacity = '1'; });
        });
        window.setTimeout(function () {
            // Keep only the newest (last) photo layer — never remove a layer
            // added by a later navigation, so rapid clicks can't strip the
            // backdrop or leave an older photo showing.
            var photos = tpx.querySelectorAll('.tpx-photo');
            for (var i = 0; i < photos.length - 1; i++) {
                if (photos[i].parentNode) { photos[i].parentNode.removeChild(photos[i]); }
            }
            if (photos.length) { photos[photos.length - 1].style.transition = ''; }
        }, 600);
    }

    // Fade the detail panel out, resolving once the fade has run.
    function fadeOutDetail(el, ms) {
        return new Promise(function (resolve) {
            if (!el) { resolve(); return; }
            el.style.opacity = '0';
            window.setTimeout(resolve, ms);
        });
    }

    var FADE_MS = 500;

    function swapTopic(href, push) {
        var current = document.querySelector('.tpx');
        var detail = current ? current.querySelector('.tpx-detail') : null;

        var fetchP = fetch(href, { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) {
                if (!r.ok) throw new Error('bad response');
                return r.text();
            });

        // Fade the current panel out and fetch in parallel; only swap once both
        // have finished, so the fade is always visible (even when cached).
        Promise.all([fetchP, fadeOutDetail(detail, FADE_MS)]).then(function (results) {
            var doc = new DOMParser().parseFromString(results[0], 'text/html');
            var fresh = doc.querySelector('.tpx');
            current = document.querySelector('.tpx');
            if (!fresh || !current) { window.location.href = href; return; }

            // Crossfade the background photo to the new topic's image.
            var photos = current.querySelectorAll('.tpx-photo');
            var curBg = bgImageOf(photos[photos.length - 1]);
            var newBg = bgImageOf(fresh.querySelector('.tpx-photo'));
            if (newBg && newBg !== curBg) { crossfadeBackground(current, newBg); }

            // Update in place, column by column, so an unchanged column never
            // re-renders (no flash, no scroll reset) — only a changed/new column
            // rebuilds and slides in. Then move the root-nav highlight and swap
            // the detail panel's content.
            reconcileColumns(current.querySelector('.tpx-sub-inner'),
                             fresh.querySelector('.tpx-sub-inner'));
            syncActive(current, fresh, 'a.tpx-nav-item');
            var freshDetail = fresh.querySelector('.tpx-detail');
            var curDetail = current.querySelector('.tpx-detail');
            if (freshDetail && curDetail) { curDetail.innerHTML = freshDetail.innerHTML; }

            // A soft-injected reCAPTCHA (the Contributions form) won't auto-render,
            // so render it explicitly. The api.js library is loaded site-wide. If
            // it isn't available, fall back to a full load so the form never breaks.
            var captcha = current.querySelector('.g-recaptcha');
            if (captcha && !captcha.firstChild) {
                if (window.grecaptcha && grecaptcha.render) {
                    try { grecaptcha.render(captcha, { sitekey: captcha.getAttribute('data-sitekey') }); }
                    catch (err) { window.location.href = href; return; }
                } else {
                    window.location.href = href; return;
                }
            }

            // Fade the freshly-swapped detail panel in from transparent.
            var newDetail = current.querySelector('.tpx-detail');
            if (newDetail) {
                newDetail.scrollTop = 0;
                newDetail.style.opacity = '0';
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () { newDetail.style.opacity = '1'; });
                });
            }

            if (doc.title) document.title = doc.title;
            if (push) window.history.pushState({ tpx: true }, '', href);
        }).catch(function () { window.location.href = href; });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a.tpx-nav-item, a.tpx-sub-link, a.tpx-index-link, a.tpx-search-result');
        if (!a) return;
        if (a.hasAttribute('data-fullload')) return;   // let this link do a full page load (form + reCAPTCHA)
        // Respect new-tab / modified clicks.
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        var href = a.getAttribute('href');
        if (!href) return;
        e.preventDefault();
        if (href === window.location.pathname) return;
        swapTopic(href, true);
    });

    window.addEventListener('popstate', function () {
        if (window.location.pathname.indexOf('/topics') === 0) {
            swapTopic(window.location.href, false);
        } else {
            window.location.reload();
        }
    });
})();
</script>
@endsection
