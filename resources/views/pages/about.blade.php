@extends('app')

@section('title', 'About Us | NPPC')

@section('head')
<style>
    .about-page { overflow: hidden; }
    .about-hero { max-width: 1200px; margin: 0 auto; padding: 80px 24px 60px; display: flex; gap: 48px; align-items: flex-start; }
    .about-title { font-size: 6rem; font-weight: 900; color: var(--fg); line-height: 1; flex: 0 0 auto; }
    .about-hero-text { font-size: 1.75rem; font-weight: 700; color: rgba(var(--fg-rgb),0.85); line-height: 1.45; max-width: 700px; }

    /* Scroll carousel */
    .about-carousel-wrapper { position: sticky; top: 108px; overflow: hidden; padding: 40px 0; }
    .about-carousel { display: flex; gap: 20px; will-change: transform; }
    .about-carousel-img { flex: 0 0 280px; height: 340px; border-radius: 8px; overflow: hidden; background: var(--surface-2); }
    .about-carousel-img img { width: 100%; height: 100%; object-fit: cover; }
    .about-carousel-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #111 0%, #1a1a2e 50%, #2a1860 100%); }

    /* Sections */
    .about-section { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
    .about-divider { height: 1px; background: rgba(var(--fg-rgb),0.1); max-width: 1200px; margin: 64px auto; }

    /* Mission */
    .about-mission { display: flex; gap: 48px; align-items: flex-start; padding: 80px 0; }
    .about-mission-label { flex: 0 0 200px; font-size: 16px; font-weight: 800; color: var(--fg); }
    .about-mission-text { font-size: 3rem; font-weight: 900; color: var(--fg); line-height: 1.15; }
    .about-mission-fade { color: rgba(var(--fg-rgb),0.2); }

    /* History */
    .about-history { display: flex; gap: 48px; padding: 80px 0; align-items: flex-start; }
    .about-history-left { flex: 1; }
    .about-history-title { font-size: 3rem; font-weight: 900; color: var(--fg); margin-bottom: 24px; line-height: 1.1; }
    .about-history-text { font-size: 18px; color: rgba(var(--fg-rgb),0.7); line-height: 1.7; font-weight: 600; }
    .about-history-image { flex: 0 0 50%; border-radius: 8px; overflow: hidden; }
    .about-history-image-placeholder { width: 100%; min-height: 500px; background: linear-gradient(135deg, #0a0a1a 0%, #1a1040 50%, #2a1860 100%); display: flex; align-items: center; justify-content: center; border-radius: 8px; }

    /* Impact */
    .about-impact { padding: 80px 0; }
    .about-impact-title { font-size: 3rem; font-weight: 900; color: var(--fg); margin-bottom: 48px; }
    .about-impact-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; }
    .about-impact-stat { text-align: center; }
    .about-impact-num { font-size: 4rem; font-weight: 900; color: var(--accent); line-height: 1; margin-bottom: 12px; }
    .about-impact-desc { font-size: 16px; color: rgba(var(--fg-rgb),0.6); line-height: 1.5; }

    /* Team */
    .about-team { display: flex; gap: 48px; padding: 80px 0; align-items: flex-start; }
    .about-team-image { flex: 0 0 45%; min-height: 400px; border-radius: 8px; overflow: hidden; background: linear-gradient(135deg, #1a1040 0%, #5660fe 100%); display: flex; align-items: center; justify-content: center; }
    .about-team-right { flex: 1; }
    .about-team-title { font-size: 3rem; font-weight: 900; color: var(--fg); margin-bottom: 20px; }
    .about-team-text { font-size: 17px; color: rgba(var(--fg-rgb),0.65); line-height: 1.7; margin-bottom: 32px; }
    .about-btn { display: inline-block; border: 1px solid rgba(var(--fg-rgb),0.3); color: var(--fg); padding: 14px 28px; font-size: 14px; font-weight: 700; text-decoration: none; transition: all 0.2s; margin-right: 12px; }
    .about-btn:hover { border-color: var(--accent); color: var(--accent); }
    .about-btn-arrow { margin-left: 8px; }

    /* Values */
    .about-values { padding: 80px 0; }
    .about-values-title { font-size: 2rem; font-weight: 900; color: var(--fg); margin-bottom: 40px; }
    .about-values-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 32px; }
    .about-value-card { border: 1px solid rgba(var(--fg-rgb),0.08); border-radius: 8px; padding: 32px; }
    .about-value-name { font-size: 20px; font-weight: 800; color: var(--fg); margin-bottom: 12px; }
    .about-value-desc { font-size: 15px; color: rgba(var(--fg-rgb),0.6); line-height: 1.7; }

    /* Searchlight */
    .spotlight-section {
        position: relative;
        width: 100%;
        height: 600px;
        background-size: cover;
        background-position: center;
        overflow: hidden;
        cursor: none;
    }
    .spotlight-overlay {
        position: absolute;
        inset: 0;
        background: #000;
        transition: opacity 0.3s;
        pointer-events: none;
    }
    .spotlight-section.active .spotlight-overlay {
        mask-image: radial-gradient(circle var(--radius) at var(--mx) var(--my), transparent 0%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0.85) 70%, #000 100%);
        -webkit-mask-image: radial-gradient(circle var(--radius) at var(--mx) var(--my), transparent 0%, rgba(0,0,0,0.3) 40%, rgba(0,0,0,0.85) 70%, #000 100%);
    }
    .spotlight-highlight {
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: 0;
        mix-blend-mode: overlay;
        transition: opacity 0.3s;
    }
    .spotlight-section.active .spotlight-highlight {
        opacity: var(--brightness);
        background: radial-gradient(circle var(--radius) at var(--mx) var(--my), rgba(255,255,255,0.7) 0%, rgba(255,255,255,0.3) 40%, transparent 70%);
    }

    @media (max-width: 768px) {
        .about-hero { flex-direction: column; padding: 48px 24px 32px; }
        .about-title { font-size: 3.5rem; }
        .about-hero-text { font-size: 1.3rem; }
        .about-mission { flex-direction: column; }
        .about-mission-text { font-size: 1.8rem; }
        .about-history { flex-direction: column; }
        .about-history-image { flex: auto; }
        .about-impact-grid { grid-template-columns: 1fr; }
        .about-team { flex-direction: column; }
        .about-values-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .about-hero { padding: 32px 16px 24px; }
        .about-title { font-size: 2.2rem; }
        .about-hero-text { font-size: 1.05rem; }
        .about-mission-text { font-size: 1.4rem; }
        .about-history-title, .about-impact-title, .about-team-title { font-size: 1.6rem !important; }
        .about-impact-num { font-size: 2.5rem !important; }
        .spotlight-section { height: 360px !important; cursor: auto !important; }
    }
</style>
@endsection

@section('body')
<div class="about-page">

    {{-- Hero --}}
    <div class="about-hero">
        <h1 class="about-title">About</h1>
        <p class="about-hero-text">The National Political Prisoner Coalition has been at the forefront of political prisoner advocacy, documenting cases, providing support, and fighting for justice for those imprisoned for their beliefs and activism.</p>
    </div>

    {{-- Horizontal scroll carousel --}}
    <div class="about-carousel-wrapper">
        <div class="about-carousel" id="about-carousel">
            @php
                $prisoners = \App\Models\Prisoner::whereNotNull('photo')->where('photo','!=','')->inRandomOrder()->limit(12)->get();
            @endphp
            @foreach($prisoners as $p)
                <div class="about-carousel-img">
                    <img src="{{ asset('storage/'.$p->photo) }}" alt="{{ $p->name }}">
                </div>
            @endforeach
            @if($prisoners->count() < 6)
                @for($i = 0; $i < 6; $i++)
                    <div class="about-carousel-img">
                        <div class="about-carousel-placeholder">
                            <img src="/images/no-image-available.svg" alt="No image available" style="width:60%; height:auto; opacity:0.8;">
                        </div>
                    </div>
                @endfor
            @endif
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Mission & Values --}}
    <div class="about-section">
        <div class="about-mission">
            <div class="about-mission-label">Mission & Values</div>
            <div class="about-mission-text">
                The National Political Prisoner Coalition works to free political prisoners, document their cases, and create fair, compassionate, <span class="about-mission-fade">and equitable systems of justice for everyone.</span>
            </div>
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Our History --}}
    <div class="about-section">
        <div class="about-history">
            <div class="about-history-left">
                <h2 class="about-history-title">Our History</h2>
                <p class="about-history-text">Since our founding, we have worked to document hundreds of cases of political imprisonment across the United States, advocate for the release of those unjustly detained, provide support to prisoners and their families, and educate the public about the ongoing reality of political repression in America.</p>
            </div>
            <div class="about-history-image">
                @if(file_exists(public_path('images/site/about-history.jpg')))
                    <img src="/images/site/about-history.jpg" alt="History of political imprisonment in the United States" style="width:100%; min-height:500px; object-fit:cover; border-radius:8px;">
                @else
                    <div class="about-history-image-placeholder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="rgba(255,255,255,0.08)" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Impact --}}
    <div class="about-section">
        <div class="about-impact">
            <h2 class="about-impact-title">Our Impact</h2>
            <div class="about-impact-grid">
                <div class="about-impact-stat">
                    <div class="about-impact-num">{{ \App\Models\Prisoner::count() }}+</div>
                    <div class="about-impact-desc">Political prisoners documented in our comprehensive database spanning decades of U.S. history</div>
                </div>
                <div class="about-impact-stat">
                    <div class="about-impact-num">{{ number_format(\App\Support\ImprisonmentDuration::totalDaysAcrossPrisoners(\App\Models\PrisonerCase::cursor())) }}</div>
                    <div class="about-impact-desc">Collective days of imprisonment endured by documented political prisoners</div>
                </div>
                <div class="about-impact-stat">
                    <div class="about-impact-num">26+</div>
                    <div class="about-impact-desc">States with documented cases of political imprisonment and government repression</div>
                </div>
            </div>
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Our Team --}}
    <div class="about-section">
        <div class="about-team">
            <div class="about-team-image">
                @if(file_exists(public_path('images/site/about-team.jpg')))
                    <img src="/images/site/about-team.jpg" alt="The NPPC team" style="width:100%; height:100%; min-height:400px; object-fit:cover;">
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="rgba(255,255,255,0.1)" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                @endif
            </div>
            <div class="about-team-right">
                <h2 class="about-team-title">Our Team</h2>
                <p class="about-team-text">We are a community of advocates, researchers, organizers, and concerned citizens who are passionate about fighting for justice and human rights. Our team brings together diverse perspectives and expertise to support political prisoners and their families.</p>
                <a href="/volunteer" class="about-btn">Get Involved<span class="about-btn-arrow">&rarr;</span></a>
                <a href="/staff" class="about-btn">Meet the Team<span class="about-btn-arrow">&rarr;</span></a>
            </div>
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Core Values --}}
    <div class="about-section">
        <div class="about-values">
            <h2 class="about-values-title">What We Stand For</h2>
            <div class="about-values-grid">
                <div class="about-value-card">
                    <div class="about-value-name">Justice & Accountability</div>
                    <div class="about-value-desc">We believe that no one should be imprisoned for their political beliefs, activism, or dissent. We hold the government accountable for the use of incarceration as a tool of political repression.</div>
                </div>
                <div class="about-value-card">
                    <div class="about-value-name">Documentation & Transparency</div>
                    <div class="about-value-desc">We maintain the most comprehensive database of political prisoners in the United States, ensuring that these cases are documented, accessible, and preserved for future generations.</div>
                </div>
                <div class="about-value-card">
                    <div class="about-value-name">Education & Awareness</div>
                    <div class="about-value-desc">We educate the public about the history and ongoing reality of political imprisonment in America through research, publications, events, and our interactive history resources.</div>
                </div>
                <div class="about-value-card">
                    <div class="about-value-name">Support & Solidarity</div>
                    <div class="about-value-desc">We provide direct support to political prisoners and their families through our outreach programs, letter-writing campaigns, and advocacy efforts at the local, state, and federal levels.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="about-divider"></div>

    {{-- Two CTA Cards --}}
    <div class="about-section">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; padding:0 0 80px;">
            <a href="/prisoner-outreach" style="position:relative; display:block; border-radius:8px; overflow:hidden; min-height:400px; text-decoration:none; {{ file_exists(public_path('images/site/about-write-letter.jpg')) ? 'background-image:url(/images/site/about-write-letter.jpg); background-size:cover; background-position:center;' : 'background:linear-gradient(135deg, #0a0a1a 0%, #1a1040 50%, #5660fe 100%);' }}">
                <div style="position:absolute; inset:0; background:linear-gradient(0deg, rgba(0,0,0,0.7) 0%, transparent 50%);"></div>
                <div style="position:absolute; bottom:0; left:0; padding:32px;">
                    <div style="font-size:2.5rem; font-weight:900; color:var(--on-dark); line-height:1.1; margin-bottom:16px;">Write a Letter</div>
                    <div style="width:48px; height:48px; display:flex; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
            <a href="/contact" style="position:relative; display:block; border-radius:8px; overflow:hidden; min-height:400px; text-decoration:none; background:var(--surface);">
                <div style="padding:32px;">
                    <div style="font-size:2.5rem; font-weight:900; color:var(--fg); line-height:1.1; margin-bottom:16px;">Get in Touch</div>
                    <p style="font-size:15px; color:rgba(var(--fg-rgb),0.6); line-height:1.7; max-width:400px;">We strongly value everyone's feedback. If you have general, media-related, or partnership inquiries, please refer to the FAQ below or click here to contact us.</p>
                </div>
                <div style="position:absolute; bottom:0; left:0; padding:32px;">
                    <div style="width:48px; height:48px; background:var(--accent); display:flex; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- Organizational Partners --}}
    @include('sections.partners-carousel')

    {{-- Searchlight Image --}}
    @php
        $spotlightEnabled = \App\Models\SiteSetting::get('about_spotlight_enabled', '1') === '1';
        $spotlightImage = \App\Models\SiteSetting::get('about_spotlight_image');
        $spotlightBrightness = \App\Models\SiteSetting::get('about_spotlight_brightness', '60');
        $spotlightRadius = \App\Models\SiteSetting::get('about_spotlight_radius', '200');
        $spotlightDimming = \App\Models\SiteSetting::get('about_spotlight_dimming', '0');
        // Suppress the retired burning-car/dog spotlight photo specifically,
        // while leaving the spotlight feature available for any other image.
        if ($spotlightImage === 'about/01KPPGN04D6K537R47C1YM9N19.png') {
            $spotlightImage = null;
        }
    @endphp
    @if($spotlightEnabled && $spotlightImage)
        <div class="spotlight-section" id="spotlight-section"
             style="background-image: url('{{ asset('storage/'.$spotlightImage) }}');"
             data-brightness="{{ $spotlightBrightness }}"
             data-radius="{{ $spotlightRadius }}"
             data-dimming="{{ $spotlightDimming }}">
            <div class="spotlight-overlay" id="spotlight-overlay" style="opacity: {{ (int)$spotlightDimming / 100 }};"></div>
            <div class="spotlight-highlight" id="spotlight-highlight" style="--brightness: {{ (int)$spotlightBrightness / 100 }};"></div>
        </div>
    @endif

    {{-- FAQ --}}
    @include('sections.faq', ['type' => 'faq'])
</div>

<script>
// Horizontal scroll carousel that moves based on page scroll
document.addEventListener('DOMContentLoaded', function() {
    var carousel = document.getElementById('about-carousel');
    if (!carousel) return;

    var wrapper = carousel.parentElement;
    var scrollSpeed = 0.3;

    window.addEventListener('scroll', function() {
        var rect = wrapper.getBoundingClientRect();
        var viewportHeight = window.innerHeight;

        // Calculate how far the carousel section is scrolled
        if (rect.top < viewportHeight && rect.bottom > 0) {
            var progress = (viewportHeight - rect.top) / (viewportHeight + rect.height);
            var maxScroll = carousel.scrollWidth - wrapper.clientWidth;
            var offset = Math.min(progress * maxScroll * scrollSpeed, maxScroll);
            carousel.style.transform = 'translateX(-' + offset + 'px)';
        }
    });
});

// Searchlight effect
document.addEventListener('DOMContentLoaded', function() {
    var section = document.getElementById('spotlight-section');
    if (!section) return;

    var overlay = document.getElementById('spotlight-overlay');
    var radius = (section.dataset.radius || '200') + 'px';
    var dimming = parseInt(section.dataset.dimming || '0') / 100;

    section.style.setProperty('--radius', radius);

    section.addEventListener('mouseenter', function() {
        section.classList.add('active');
    });

    section.addEventListener('mouseleave', function() {
        section.classList.remove('active');
        overlay.style.opacity = dimming;
    });

    section.addEventListener('mousemove', function(e) {
        var rect = section.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;
        section.style.setProperty('--mx', x + 'px');
        section.style.setProperty('--my', y + 'px');
    });
});
</script>
@endsection
