<x-layout title="Mein Konto">
    <section class="page-head">
        <div class="container">
            <div class="account-head">
                <div>
                    <p class="eyebrow">Mein Konto</p>
                    <h1 class="page-head__title">Hallo, {{ $user->name }}</h1>
                </div>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button--secondary" type="submit">Abmelden</button>
                </form>
            </div>
            <nav class="account-nav" aria-label="Kontobereiche">
                <a href="{{ route('account.show') }}" class="is-active">Übersicht</a>
                <a href="{{ route('account.settings') }}">Einstellungen</a>
                <a href="{{ route('account.settings') }}#bag">Mein Bag</a>
                <a href="{{ route('players.show', $user->username) }}">Mein Profil</a>
                <a href="{{ route('community.index') }}">Community</a>
                <a href="{{ route('chats.index') }}">Nachrichten</a>
                @if ($user->role->canEditContent())
                    <a href="{{ route('admin.dashboard') }}">Admin-Bereich</a>
                @endif
            </nav>
        </div>
    </section>

    @unless ($user->hasVerifiedEmail())
        <div class="container">
            <div class="notice" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem">
                <span>Bitte bestätige deine E-Mail-Adresse <strong>{{ $user->email }}</strong> über den Link in unserer Mail.</span>
                <form method="post" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="link-button" type="submit">Link erneut senden</button>
                </form>
            </div>
        </div>
    @endunless

    <section class="section section--tight">
        <div class="container">
            {{-- Kennzahlen --}}
            <div class="stat-tiles">
                <div class="stat-tile"><span class="stat-tile__value num">{{ $stats['rounds'] }}</span><span class="stat-tile__label">Runden</span></div>
                <div class="stat-tile"><span class="stat-tile__value num">{{ $stats['courses'] }}</span><span class="stat-tile__label">Plätze gespielt</span></div>
                <div class="stat-tile"><span class="stat-tile__value num">{{ $stats['best'] ?? '–' }}</span><span class="stat-tile__label">Beste 18-Loch-Runde</span></div>
                <div class="stat-tile"><span class="stat-tile__value num">{{ $stats['average'] !== null ? number_format($stats['average'], 1, ',', '') : '–' }}</span><span class="stat-tile__label">Schnitt 18 Loch</span></div>
            </div>

            <div class="member-grid">
                {{-- Club --}}
                <div class="panel">
                    <h2>Mein Club</h2>
                    @if ($homeCourse)
                        <x-course-card :course="$homeCourse" />
                        <div class="member-cta__actions" style="margin-top: var(--space-4)">
                            <a class="button button--primary" href="{{ route('chats.club', $homeCourse) }}">Club-Chat</a>
                            <a class="button button--secondary" href="{{ route('clubs.show', $homeCourse) }}">Mitglieder</a>
                        </div>
                    @else
                        <p class="muted">Öffne die Seite deines Platzes und tippe auf „Mitglied werden“ – dann findest du andere Mitglieder und den Club-Chat.</p>
                        <a class="button button--secondary" href="{{ route('courses.index') }}">Platz suchen</a>
                    @endif
                </div>

                {{-- Mein Bag --}}
                <div class="panel">
                    <h2>Mein Bag</h2>
                    @if ($bag->isEmpty())
                        <p class="muted">Trag deine Schlägerdistanzen ein. Auf jeder Bahn siehst du dann deinen Spielplan und welche Hindernisse du erreichst.</p>
                        <a class="button button--primary" href="{{ route('account.settings') }}#bag">Bag einrichten</a>
                    @else
                        <ul class="bag-list">
                            @foreach ($bag->carries as $club => $carry)
                                <li><span>{{ \App\Modules\Player\Support\Bag::label($club) }}</span><span class="num">{{ distance($carry) }}</span></li>
                            @endforeach
                        </ul>
                        <a class="link-arrow" href="{{ route('account.settings') }}#bag">Bag bearbeiten <x-icon name="arrow-right" :size="16" /></a>
                    @endif
                </div>
            </div>

            {{-- Runden-Tagebuch --}}
            <div class="panel">
                <h2>Runden-Tagebuch</h2>
                @if ($rounds->isEmpty())
                    <p class="muted">Noch keine Runden. Eine Runde trägst du auf der Seite des Platzes ein („Runde eintragen“).</p>
                @else
                    <ul class="round-list">
                        @foreach ($rounds as $round)
                            <li class="round-list__item">
                                <span class="num">{{ $round->played_on->format('d.m.Y') }}</span>
                                <span>
                                    @if ($round->course?->isPublished())
                                        <a href="{{ route('courses.show', $round->course) }}">{{ $round->course->name }}</a>
                                    @else
                                        {{ $round->course?->name }}
                                    @endif
                                    <span class="muted">· {{ $round->holes_played }} Löcher @if ($round->teeSet) · {{ $round->teeSet->name }} @endif</span>
                                    @if ($round->note)<br><span class="muted">{{ $round->note }}</span>@endif
                                </span>
                                <strong class="num">{{ $round->score ?? '–' }}</strong>
                                <form method="post" action="{{ route('rounds.destroy', $round) }}" onsubmit="return confirm('Runde löschen?')">
                                    @csrf @method('delete')
                                    <button class="link-button link-button--muted" type="submit" aria-label="Runde löschen">Löschen</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Notizen --}}
            <div class="panel">
                <h2>Meine Notizen</h2>
                @if ($notes->isEmpty())
                    <p class="muted">Auf jeder Bahnseite kannst du dir eine eigene Notiz machen – sie erscheint hier und in deinem Yardage Book.</p>
                @else
                    <ul class="note-list">
                        @foreach ($notes as $note)
                            @continue (! $note->hole?->course?->isPublished())
                            <li>
                                <a href="{{ route('holes.show', ['course' => $note->hole->course, 'number' => $note->hole->number]) }}">
                                    <strong>{{ $note->hole->course->name }} · Bahn {{ $note->hole->number }}</strong>
                                </a>
                                <p>{{ \Illuminate\Support\Str::limit($note->body, 180) }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>

    <section class="section section--tight" id="merkliste">
        <div class="container">
            <div class="section__head">
                <div>
                    <h2 class="section__title section__title--small">Merkliste</h2>
                    <p class="muted">Plätze merken: auf der Platzseite auf „Merken“ tippen.</p>
                </div>
            </div>
            @if ($favorites->isEmpty())
                <div class="empty-state">
                    <p>Noch keine Plätze gemerkt.</p>
                    <a class="button button--primary" href="{{ route('courses.index') }}">Plätze entdecken</a>
                </div>
            @else
                <div class="course-grid">
                    @foreach ($favorites as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($recent->isNotEmpty())
        <section class="section">
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
</x-layout>
