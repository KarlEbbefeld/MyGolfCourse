<x-layout title="Community">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Community</p>
            <h1 class="page-head__title">Golfer finden, Clubs entdecken</h1>
            <p class="page-head__lead">Folge anderen Spielern, werdet Freunde und tauscht euch im Club-Chat aus.</p>

            <form class="filters" method="get" action="{{ route('community.index') }}" role="search">
                <div class="search-field search-field--compact">
                    <label class="visually-hidden" for="player-search">Name oder Nutzername</label>
                    <x-icon name="search" :size="18" />
                    <input id="player-search" name="q" type="search" value="{{ $search }}" placeholder="Golfer suchen: Name oder Nutzername">
                </div>
                <button class="button button--primary" type="submit">Suchen</button>
            </form>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($search !== '')
                <div class="panel">
                    <h2>Suchergebnisse für „{{ $search }}“</h2>
                    @if ($results->isEmpty())
                        <p class="muted">Niemand gefunden. Gefunden werden öffentliche Profile und deine Freunde.</p>
                    @else
                        <ul class="player-list">
                            @foreach ($results as $player)
                                <x-player-row :player="$player" />
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @unless ($me->profile_public)
                <p class="notice" style="margin-bottom: var(--space-6)">Dein Profil ist <strong>privat</strong> – andere finden dich nicht in der Suche. <a href="{{ route('account.settings') }}">Öffentlich schalten</a></p>
            @endunless

            <div class="member-grid">
                <div class="panel">
                    <h2>Mein Club</h2>
                    @if ($me->club)
                        <p><strong>{{ $me->club->name }}</strong><br><span class="muted">{{ $clubMemberCount }} {{ $clubMemberCount === 1 ? 'Mitglied' : 'Mitglieder' }} auf My Golf Course</span></p>
                        <div class="member-cta__actions">
                            <a class="button button--primary" href="{{ route('chats.club', $me->club) }}">Club-Chat öffnen</a>
                            <a class="button button--secondary" href="{{ route('clubs.show', $me->club) }}">Alle Mitglieder</a>
                        </div>
                        @if ($clubMembers->isNotEmpty())
                            <h3 class="panel-subtitle">Mitglieder</h3>
                            <ul class="player-list">
                                @foreach ($clubMembers as $player)
                                    <x-player-row :player="$player" />
                                @endforeach
                            </ul>
                        @endif
                    @else
                        <p class="muted">Du hast noch keinen Club. Öffne die Seite deines Platzes und tippe auf „Mitglied werden“ – dann bekommst du Zugang zum Club-Chat.</p>
                        <a class="button button--secondary" href="{{ route('courses.index') }}">Platz suchen</a>
                    @endif
                </div>

                <div class="panel">
                    <h2>Freunde <span class="muted num">{{ $friends->count() }}</span></h2>
                    <p class="muted">Freunde seid ihr, wenn ihr euch gegenseitig folgt.</p>
                    @if ($friends->isEmpty())
                        <p class="muted">Noch keine Freunde.</p>
                    @else
                        <ul class="player-list">
                            @foreach ($friends as $player)
                                <x-player-row :player="$player" />
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="member-grid">
                <div class="panel">
                    <h2>Folgen dir</h2>
                    @if ($followers->isEmpty())
                        <p class="muted">Noch niemand.</p>
                    @else
                        <ul class="player-list">
                            @foreach ($followers as $player)
                                <x-player-row :player="$player" :note="$friends->contains('id', $player->id) ? 'Freund' : null" />
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="panel">
                    <h2>Du folgst</h2>
                    @if ($following->isEmpty())
                        <p class="muted">Du folgst noch niemandem.</p>
                    @else
                        <ul class="player-list">
                            @foreach ($following as $player)
                                <x-player-row :player="$player" />
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layout>
