@php
    $defaultTee = $teeSets->first();
    $firstHole = $holes->first();
@endphp
<x-layout :title="$course->name" :description="$course->short_description ?? $course->name">
    <x-slot:head>
        <script type="application/ld+json">{!! $jsonLd !!}</script>
    </x-slot:head>

    <section @class(['course-hero', 'course-hero--photo' => $course->hero_image_url])>
        @if ($course->hero_image_url)
            <div class="course-hero__photo">
                <img src="{{ \App\Modules\Explorer\Support\HoleExplorer::mediaUrl($course->hero_image_url) }}" alt="" fetchpriority="high">
            </div>
        @endif

        <div class="container course-hero__inner">
            <div>
                <nav class="breadcrumb" aria-label="Brotkrumen">
                    <a href="{{ route('courses.index') }}">Golfplätze</a><span aria-hidden="true">/</span><span>{{ $course->name }}</span>
                </nav>
                <p class="eyebrow">
                    {{ $course->location() }}@if ($course->region) · {{ $course->region }}@endif
                    @if ($course->is_demo) <span class="badge">Demo</span> @endif
                </p>
                <h1 class="course-hero__title">{{ $course->name }}</h1>
                @if ($course->short_description)
                    <p class="course-hero__lead">{{ $course->short_description }}</p>
                @endif

                <div class="stats">
                    <div class="stats__item"><span class="stats__value">{{ $holes->count() }}</span><span class="stats__label">Bahnen</span></div>
                    <div class="stats__item"><span class="stats__value">{{ $parTotal }}</span><span class="stats__label">Par</span></div>
                    @if ($defaultTee && ($totals[$defaultTee->id] ?? 0) > 0)
                        <div class="stats__item"><span class="stats__value">{{ distance($totals[$defaultTee->id]) }}</span><span class="stats__label">Länge {{ $defaultTee->name }}</span></div>
                    @endif
                </div>

                <div class="course-hero__actions">
                    @if ($firstHole)
                        <a class="button button--light button--large" href="{{ route('holes.show', ['course' => $course, 'number' => $firstHole->number]) }}">
                            Bahn {{ $firstHole->number }} erkunden <x-icon name="arrow-right" :size="18" />
                        </a>
                    @endif
                    @auth
                        <form method="post" action="{{ route('favorites.toggle', $course) }}" data-favorite-form>
                            @csrf
                            <button class="button button--outline-light button--large" type="submit" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" data-favorite-button>
                                <x-icon name="heart" :size="18" /> <span data-favorite-label>{{ $isFavorite ? 'Gemerkt' : 'Merken' }}</span>
                            </button>
                        </form>
                        <a class="button button--outline-light button--large" href="{{ route('yardage-book', $course) }}">
                            <x-icon name="plan" :size="18" /> Yardage Book
                        </a>
                    @else
                        <a class="button button--outline-light button--large" href="{{ route('favorites.login', $course) }}">
                            <x-icon name="heart" :size="18" /> Merken
                        </a>
                    @endauth
                </div>
            </div>

            @unless ($course->hero_image_url)
                <div class="course-hero__map">
                    <x-course-diagram :course="$course" :aspect="0.6875" />
                </div>
            @endunless
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section__head">
                <div>
                    <p class="eyebrow">Scorecard</p>
                    <h2 class="section__title">Alle Bahnen</h2>
                </div>
                <p class="muted">Bahn anklicken, um sie zu erkunden.</p>
            </div>

            <div class="scorecard" role="region" aria-label="Scorecard" tabindex="0">
                <table class="scorecard__table num">
                    <thead>
                        <tr>
                            <th scope="col">Bahn</th>
                            <th scope="col">Par</th>
                            <th scope="col">HCP</th>
                            @foreach ($teeSets as $teeSet)
                                <th scope="col"><span class="tee-dot tee-dot--{{ $teeSet->color->value }}" aria-hidden="true"></span>{{ $teeSet->name }}</th>
                            @endforeach
                            <th scope="col"><span class="visually-hidden">Bahn öffnen</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($holes as $hole)
                            @php($url = route('holes.show', ['course' => $course, 'number' => $hole->number]))
                            <tr class="scorecard__row" data-href="{{ $url }}">
                                <th scope="row"><a href="{{ $url }}" aria-label="Bahn {{ $hole->number }} erkunden">{{ $hole->number }}</a></th>
                                <td>{{ $hole->par }}</td>
                                <td>{{ $hole->stroke_index ?? '–' }}</td>
                                @foreach ($teeSets as $teeSet)
                                    @php($tee = $hole->teeFor($teeSet))
                                    <td>{{ $tee && $tee->length_m > 0 ? \App\Core\Support\Units::value($tee->length_m) : '–' }}</td>
                                @endforeach
                                <td class="scorecard__go"><x-icon name="arrow-right" :size="16" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">Gesamt</th>
                            <td>{{ $parTotal }}</td>
                            <td></td>
                            @foreach ($teeSets as $teeSet)
                                <td>{{ ($totals[$teeSet->id] ?? 0) > 0 ? number_format(\App\Core\Support\Units::value($totals[$teeSet->id]), 0, ',', '.') : '–' }}</td>
                            @endforeach
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="scorecard__note">Längen in {{ \App\Core\Support\Units::label() }}.
                @foreach ($teeSets->filter(fn ($t) => $t->course_rating) as $teeSet)
                    {{ $teeSet->name }}: CR {{ number_format($teeSet->course_rating, 1, ',', '') }} · Slope {{ $teeSet->slope }}@if (! $loop->last) &nbsp;|&nbsp; @endif
                @endforeach
            </p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @auth
                <div class="member-grid">
                    <div class="panel">
                        <h2>Runde eintragen</h2>
                        <p class="muted">Für dein Runden-Tagebuch und deine Statistik.</p>
                        @if ($errors->any())
                            <div class="error-box" role="alert" style="margin-bottom: var(--space-4)">
                                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                            </div>
                        @endif
                        <form method="post" action="{{ route('rounds.store', $course) }}" class="form-stack">
                            @csrf
                            <div class="form-row">
                                <label class="field"><span>Gespielt am</span>
                                    <input type="date" name="played_on" value="{{ old('played_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></label>
                                <label class="field"><span>Löcher</span>
                                    <select name="holes_played">
                                        @foreach ([9, 18, 27] as $count)
                                            <option value="{{ $count }}" @selected((int) old('holes_played', $course->holes_count) === $count)>{{ $count }}</option>
                                        @endforeach
                                    </select></label>
                            </div>
                            <div class="form-row">
                                <label class="field"><span>Abschlag</span>
                                    <select name="tee_set_id">
                                        <option value="">–</option>
                                        @foreach ($teeSets as $teeSet)
                                            <option value="{{ $teeSet->id }}" @selected((int) old('tee_set_id', $defaultTee?->id) === $teeSet->id)>{{ $teeSet->name }}</option>
                                        @endforeach
                                    </select></label>
                                <label class="field"><span>Schläge (optional)</span>
                                    <input type="number" name="score" min="18" max="250" value="{{ old('score') }}" inputmode="numeric"></label>
                            </div>
                            <label class="field"><span>Notiz (optional)</span>
                                <input name="note" maxlength="500" value="{{ old('note') }}" placeholder="z. B. Wind aus West, Grüns schnell"></label>
                            <div><button class="button button--primary" type="submit">Runde speichern</button></div>
                        </form>
                    </div>
                    <div class="panel">
                        <h2>Deine Runden hier</h2>
                        @if ($myRounds->isEmpty())
                            <p class="muted">Noch keine Runde auf diesem Platz eingetragen.</p>
                        @else
                            <ul class="round-list">
                                @foreach ($myRounds as $round)
                                    <li class="round-list__item">
                                        <span class="num">{{ $round->played_on->format('d.m.Y') }}</span>
                                        <span>{{ $round->holes_played }} Löcher @if ($round->teeSet) · {{ $round->teeSet->name }} @endif</span>
                                        <strong class="num">{{ $round->score ?? '–' }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="club-box">
                            <p class="club-box__title">{{ $isHome ? 'Du bist Mitglied in diesem Club' : 'Spielst du hier?' }}</p>
                            <p class="muted">{{ $isHome ? 'Im Club-Chat tauschst du dich mit den anderen Mitgliedern aus.' : 'Ordne dich dem Club zu – dann findest du andere Mitglieder und den Club-Chat.' }}</p>
                            <div class="member-cta__actions">
                                @if ($isHome)
                                    <a class="button button--primary" href="{{ route('chats.club', $course) }}">Zum Club-Chat</a>
                                @endif
                                <a class="button button--secondary" href="{{ route('clubs.show', $course) }}">Mitglieder ansehen</a>
                                <form method="post" action="{{ route('clubs.toggle', $course) }}">
                                    @csrf
                                    <button class="button button--secondary" type="submit">{{ $isHome ? 'Mitgliedschaft beenden' : 'Mitglied werden' }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <aside class="member-cta member-cta--wide">
                    <div>
                        <p class="member-cta__title">Mit Konto noch besser vorbereitet</p>
                        <p>Persönlicher Spielplan aus deinen Schlägerdistanzen, Notizen je Bahn, Runden-Tagebuch und ein Yardage Book dieses Platzes zum Ausdrucken – kostenlos.</p>
                    </div>
                    <div class="member-cta__actions">
                        <a class="button button--primary" href="{{ route('register') }}">Konto erstellen</a>
                        <a class="button button--secondary" href="{{ route('login') }}">Anmelden</a>
                    </div>
                </aside>
            @endauth
        </div>
    </section>

    <section class="section section--white">
        <div class="container course-about">
            <div class="course-about__text">
                <p class="eyebrow">Über den Platz</p>
                <h2 class="section__title">{{ $course->name }}</h2>
                <div style="margin-top: var(--space-6)">
                    @forelse (preg_split('/\n\s*\n/', trim((string) $course->description)) as $paragraph)
                        @if (trim($paragraph) !== '')
                            <p>{{ trim($paragraph) }}</p>
                        @endif
                    @empty
                    @endforelse
                </div>
            </div>
            <aside class="info-card">
                <dl>
                    <div>
                        <dt>Club</dt>
                        <dd>{{ $course->club_name ?? $course->name }}@if ($course->street)<br>{{ $course->street }}@endif<br>{{ $course->location() }}</dd>
                    </div>
                    @if ($course->phone)
                        <div><dt>Telefon</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $course->phone) }}">{{ $course->phone }}</a></dd></div>
                    @endif
                    @if ($course->website_url)
                        <div><dt>Website</dt><dd><a href="{{ $course->website_url }}" rel="noopener" target="_blank">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($course->website_url, '/')) }} ↗</a></dd></div>
                    @endif
                    <div>
                        <dt>Aufnahmen</dt>
                        <dd>{{ $course->capture_date ? $course->capture_date->translatedFormat('F Y') : 'Noch keine eigenen Aufnahmen' }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>
</x-layout>
