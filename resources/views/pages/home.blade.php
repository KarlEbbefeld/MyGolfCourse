<x-layout body-class="page-home" overlay-header>
    <section class="home-hero">
        <div class="home-hero__media" aria-hidden="true">
            @if ($heroFlyover)
                {{-- Echter Überflug, stumm in Schleife; bei „weniger Bewegung“ angehalten (public/js/app.js) --}}
                <video autoplay muted loop playsinline preload="metadata" data-hero-video
                       @if ($heroFlyover->posterSrc()) poster="{{ $heroFlyover->posterSrc() }}" @endif>
                    <source src="{{ $heroFlyover->videoSrc() }}" type="video/mp4">
                </video>
            @elseif ($courses->first())
                <x-course-diagram :course="$courses->first()" :aspect="0.56" :numbers="false" />
            @endif
        </div>

        <div class="home-hero__content">
            <p class="eyebrow">Golfplätze digital erkunden</p>
            <h1 class="home-hero__title">Jede Bahn kennen, bevor du abschlägst.</h1>
            <p class="home-hero__lead">Überfliege jede Bahn mit der Drohne, sieh dich in 360° um und plane deine Schläge mit vermessenen Bäumen, Bunkern und Distanzen.</p>

            <form class="search-field" action="{{ route('courses.index') }}" method="get" role="search">
                <label class="visually-hidden" for="home-search">Golfplatz, Ort oder Club</label>
                <x-icon name="search" />
                <input id="home-search" name="q" type="search" placeholder="Golfplatz, Ort oder Club suchen" autocomplete="off">
                <button class="button button--primary" type="submit">Suchen</button>
            </form>

            @if ($courses->isNotEmpty())
                <p class="home-hero__suggestions">
                    <span>Beliebt:</span>
                    @foreach ($courses->take(3) as $course)
                        <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a>
                    @endforeach
                </p>
            @endif

            <div class="hero-facts">
                <p class="hero-facts__item"><x-icon name="video" /><span><strong>Drohnenüberflug</strong>Vom Abschlag bis zum Grün</span></p>
                <p class="hero-facts__item"><x-icon name="sphere" /><span><strong>360° wie Street View</strong>Umsehen an jeder Position</span></p>
                <p class="hero-facts__item"><x-icon name="ruler" /><span><strong>Vor Ort vermessen</strong>Baumhöhen und Distanzen</span></p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section__head">
                <div>
                    <p class="eyebrow">Drei Blickwinkel</p>
                    <h2 class="section__title">So siehst du jede Bahn</h2>
                </div>
                <a class="link-arrow" href="{{ route('about') }}">So funktioniert’s <x-icon name="arrow-right" :size="16" /></a>
            </div>
            <div class="modes">
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="video" :size="26" /></span>
                    <h3>Überflug</h3>
                    <p>Die Drohne fliegt die Bahn ab und hält an den entscheidenden Stellen: Landezone, Dogleg, vor dem Grün.</p>
                    <span class="mode-card__number">01</span>
                </article>
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="sphere" :size="26" /></span>
                    <h3>360°-Ansicht</h3>
                    <p>Klick dich wie in Street View von Position zu Position und sieh dich frei in alle Richtungen um.</p>
                    <span class="mode-card__number">02</span>
                </article>
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="plan" :size="26" /></span>
                    <h3>Plan &amp; Hindernisse</h3>
                    <p>Die Bahngrafik mit allen Hindernissen, Höhen und Distanzen – für deine Strategie vom gewählten Abschlag.</p>
                    <span class="mode-card__number">03</span>
                </article>
            </div>
        </div>
    </section>

    @if ($courses->isNotEmpty())
        <section class="section section--white">
            <div class="container">
                <div class="section__head">
                    <div>
                        <p class="eyebrow">Plätze</p>
                        <h2 class="section__title">Plätze entdecken</h2>
                    </div>
                    <a class="link-arrow" href="{{ route('courses.index') }}">Alle {{ $courseCount }} Plätze <x-icon name="arrow-right" :size="16" /></a>
                </div>
                <div class="course-grid">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($recent->isNotEmpty())
        <section class="section section--tight">
            <div class="container">
                <div class="section__head">
                    <h2 class="section__title section__title--small">Zuletzt angesehen</h2>
                </div>
                <div class="course-grid">
                    @foreach ($recent as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container split">
            <div>
                <p class="eyebrow">Präzision statt Schätzung</p>
                <h2 class="section__title">Wie hoch ist der Baum am Dogleg wirklich?</h2>
                <p class="section__lead">Wir vermessen strategisch wichtige Bäume und Hindernisse vor Ort und verorten sie auf der Bahn. So weißt du vor der Runde, ob die Abkürzung drin ist.</p>
                <ul class="checklist">
                    <li><x-icon name="check" /><span><strong>Baumhöhen mit Messgerät</strong> – mit Genauigkeit und Messdatum</span></li>
                    <li><x-icon name="check" /><span><strong>Distanzen vom gewählten Abschlag</strong> – Carry über Bunker und Wasser</span></li>
                    <li><x-icon name="check" /><span><strong>Aktuelle Aufnahmen</strong> – mit Datum, damit du weißt, wie frisch die Bilder sind</span></li>
                </ul>
            </div>
            <div class="sample-card" aria-label="Beispiel einer Hindernis-Info">
                <p class="sample-card__type">Baum · Bahn 7</p>
                <p class="sample-card__title">Eiche am Dogleg links</p>
                <dl class="sample-card__rows">
                    <div><dt>Höhe</dt><dd>ca. 17 m (± 1 m)</dd></div>
                    <div><dt>Vom gelben Abschlag</dt><dd>182 m</dd></div>
                    <div><dt>Danach bis Grünmitte</dt><dd>145 m</dd></div>
                    <div><dt>Gemessen</dt><dd>05/2027</dd></div>
                </dl>
            </div>
        </div>
    </section>

    <section class="section section--dark">
        <div class="container cta-band">
            <div>
                <p class="eyebrow">Für Golfclubs</p>
                <h2 class="section__title">Ihr Platz auf My Golf Course</h2>
                <p class="section__lead">Wir fliegen, vermessen und präsentieren Ihren Platz – als digitale Platzbegehung für Gäste und Mitglieder.</p>
            </div>
            <div class="cta-band__actions">
                @if (config('legal.email'))
                    <a class="button button--light button--large" href="mailto:{{ config('legal.email') }}?subject=Unser%20Platz%20auf%20My%20Golf%20Course">
                        <x-icon name="mail" :size="18" /> Kontakt aufnehmen
                    </a>
                @endif
                <a class="button button--outline-light button--large" href="{{ route('courses.index') }}">Beispiele ansehen</a>
            </div>
        </div>
    </section>
</x-layout>
