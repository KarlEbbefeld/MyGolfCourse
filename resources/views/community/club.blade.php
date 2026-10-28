<x-layout :title="'Club · '.$course->name">
    <section class="page-head">
        <div class="container">
            <nav class="breadcrumb breadcrumb--light" aria-label="Brotkrumen">
                <a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a><span aria-hidden="true">/</span><span>Club</span>
            </nav>
            <p class="eyebrow">Club-Community</p>
            <h1 class="page-head__title">{{ $course->club_name ?? $course->name }}</h1>
            <p class="page-head__lead">{{ $memberCount }} {{ $memberCount === 1 ? 'Mitglied' : 'Mitglieder' }} auf My Golf Course.</p>

            <div class="member-cta__actions" style="margin-top: var(--space-6)">
                @auth
                    @if ($isMember)
                        <a class="button button--primary button--large" href="{{ route('chats.club', $course) }}">Club-Chat öffnen</a>
                    @endif
                    <form method="post" action="{{ route('clubs.toggle', $course) }}">
                        @csrf
                        <button class="button {{ $isMember ? 'button--secondary' : 'button--primary' }} button--large" type="submit">
                            {{ $isMember ? 'Mitgliedschaft beenden' : 'Mitglied werden' }}
                        </button>
                    </form>
                @else
                    <a class="button button--primary button--large" href="{{ route('register') }}">Konto erstellen und beitreten</a>
                    <a class="button button--secondary button--large" href="{{ route('login') }}">Anmelden</a>
                @endauth
            </div>
            <p class="muted" style="margin-top: var(--space-3); font-size: var(--text-sm)">Mitgliedschaft ist eine eigene Angabe und wird nicht vom Club geprüft. Du kannst zu einem Club gehören.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="panel">
                <h2>Mitglieder</h2>
                @if ($members->isEmpty())
                    <p class="muted">Noch keine sichtbaren Mitglieder. Private Profile erscheinen hier nur für Freunde.</p>
                @else
                    <ul class="player-list player-list--grid">
                        @foreach ($members as $player)
                            <x-player-row :player="$player" />
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </section>
</x-layout>
