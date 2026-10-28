<x-layout :title="$player->name" :description="'Spielerprofil von '.$player->name.' auf My Golf Course'">
    <x-slot:head>
        @unless ($player->profile_public)
            <meta name="robots" content="noindex">
        @endunless
    </x-slot:head>

    <section class="profile-hero">
        <div class="container profile-hero__inner">
            <x-avatar :user="$player" size="xl" />
            <div class="profile-hero__text">
                <p class="eyebrow eyebrow--plain">
                    {{ '@'.$player->username }}
                    @if (! $player->profile_public) <span class="badge badge--dark">Privat</span> @endif
                    @if ($viewer && ! $isMe && $isFollowing && $followsMe) <span class="badge">Freunde</span>
                    @elseif ($viewer && ! $isMe && $followsMe) <span class="badge">Folgt dir</span> @endif
                </p>
                <h1 class="profile-hero__name">{{ $player->name }}</h1>
                @if ($visible)
                    <p class="profile-hero__meta">
                        @if ($club) <a href="{{ route('clubs.show', $club) }}">{{ $club->name }}</a> @endif
                        @if ($player->handicap !== null) <span class="num">· HCP {{ number_format($player->handicap, 1, ',', '') }}</span> @endif
                    </p>
                @endif
                <div class="profile-hero__stats">
                    <span><strong class="num">{{ $stats['followers'] }}</strong> Follower</span>
                    <span><strong class="num">{{ $stats['following'] }}</strong> folgt</span>
                    @if ($visible)
                        <span><strong class="num">{{ $stats['rounds'] }}</strong> Runden</span>
                        <span><strong class="num">{{ $stats['courses'] }}</strong> Plätze</span>
                    @endif
                </div>
            </div>

            <div class="profile-hero__actions">
                @if ($isMe)
                    <a class="button button--light" href="{{ route('account.settings') }}">Profil bearbeiten</a>
                @elseif ($viewer)
                    @unless ($iBlocked)
                        <form method="post" action="{{ route('players.follow', $player->username) }}">
                            @csrf
                            <button class="button {{ $isFollowing ? 'button--outline-light' : 'button--light' }}" type="submit">
                                {{ $isFollowing ? 'Entfolgen' : ($followsMe ? 'Zurückfolgen' : 'Folgen') }}
                            </button>
                        </form>
                        @if ($canMessage)
                            <form method="post" action="{{ route('players.message', $player->username) }}">
                                @csrf
                                <button class="button button--outline-light" type="submit"><x-icon name="mail" :size="18" /> Nachricht</button>
                            </form>
                        @endif
                    @endunless
                    <details class="more-menu">
                        <summary class="button button--outline-light" aria-label="Weitere Aktionen">•••</summary>
                        <div class="more-menu__panel">
                            <form method="post" action="{{ route('players.block', $player->username) }}" onsubmit="return confirm('{{ $iBlocked ? 'Blockierung aufheben?' : 'Person blockieren? Ihr folgt euch dann nicht mehr und könnt euch nicht schreiben.' }}')">
                                @csrf
                                <button class="more-menu__item" type="submit">{{ $iBlocked ? 'Blockierung aufheben' : 'Blockieren' }}</button>
                            </form>
                            <form method="post" action="{{ route('players.report', $player->username) }}" class="more-menu__report">
                                @csrf
                                <label class="field"><span>Melden</span>
                                    <select name="reason" required>
                                        @foreach ($reasons as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select></label>
                                <input name="note" maxlength="1000" placeholder="Was ist passiert? (optional)" class="input">
                                <button class="button button--secondary" type="submit">Meldung senden</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a class="button button--light" href="{{ route('login') }}">Anmelden, um zu folgen</a>
                @endif
            </div>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($iBlocked)
                <div class="empty-state"><p>Du hast diese Person blockiert.</p></div>
            @elseif (! $visible)
                <div class="empty-state">
                    <p><strong>Dieses Profil ist privat.</strong><br>Club, Handicap und Runden sehen nur Freunde – also Personen, die sich gegenseitig folgen.</p>
                </div>
            @else
                <div class="member-grid">
                    <div class="panel">
                        <h2>Über {{ $isMe ? 'mich' : \Illuminate\Support\Str::before($player->name, ' ') }}</h2>
                        @if ($player->bio)
                            <p style="white-space: pre-line">{{ $player->bio }}</p>
                        @else
                            <p class="muted">Noch keine Beschreibung.</p>
                        @endif
                    </div>
                    <div class="panel">
                        <h2>Freunde</h2>
                        @if ($friends->isEmpty())
                            <p class="muted">Noch keine Freunde.</p>
                        @else
                            <ul class="player-list">
                                @foreach ($friends as $friend)
                                    <x-player-row :player="$friend" />
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>

                @if ($playedCourses->isNotEmpty())
                    <div class="section__head" style="margin-top: var(--space-10)">
                        <h2 class="section__title section__title--small">Gespielte Plätze</h2>
                    </div>
                    <div class="course-grid">
                        @foreach ($playedCourses as $course)
                            <x-course-card :course="$course->loadMissing('holes.tees')" />
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </section>
</x-layout>
