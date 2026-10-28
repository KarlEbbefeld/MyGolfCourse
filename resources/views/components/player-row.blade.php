@props(['player', 'note' => null])
<li class="player-row">
    <a class="player-row__link" href="{{ route('players.show', $player->username) }}">
        <x-avatar :user="$player" />
        <span class="player-row__text">
            <strong>{{ $player->name }}</strong>
            <span class="muted">{{ '@'.$player->username }}@if ($player->profile_public && $player->club) · {{ $player->club->name }}@endif</span>
        </span>
        @if ($note)
            <span class="badge">{{ $note }}</span>
        @endif
    </a>
</li>
