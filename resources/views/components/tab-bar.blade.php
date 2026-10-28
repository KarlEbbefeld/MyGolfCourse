{{--
    Menüleiste unten auf dem Handy (wie im Golf Marketplace): Start, Community, Plätze (runder Knopf in der Mitte), Nachrichten, Merkliste.
    Nur unter 48rem sichtbar; im Chat und auf Bahnseiten blendet das Layout sie aus.
    Nachrichten-Zähler aktualisiert public/js/header-badges.js (data-badge="messages").
--}}
@props(['user' => null, 'unread' => 0])

@php
    $items = [
        ['href' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'home', 'label' => 'Start'],
        ['href' => $user ? route('community.index') : route('register'), 'active' => request()->routeIs('community.*', 'players.*', 'clubs.*'), 'icon' => 'user', 'label' => 'Community'],
        ['href' => route('courses.index'), 'active' => request()->routeIs('courses.*', 'holes.*', 'yardage-book'), 'icon' => 'flag', 'label' => 'Plätze', 'primary' => true],
        ['href' => $user ? route('chats.index') : route('login'), 'active' => request()->routeIs('chats.*'), 'icon' => 'mail', 'label' => 'Nachrichten', 'badge' => 'messages'],
        ['href' => $user ? route('account.show').'#merkliste' : route('login'), 'active' => request()->routeIs('account.show'), 'icon' => 'heart', 'label' => 'Merkliste'],
    ];
@endphp

<nav class="tab-bar" aria-label="Schnellnavigation">
    <ul class="tab-bar__list">
        @foreach ($items as $item)
            <li>
                <a href="{{ $item['href'] }}"
                   @if (isset($item['badge'])) data-badge="{{ $item['badge'] }}" @endif
                   @class(['tab-bar__link', 'is-active' => $item['active'], 'tab-bar__link--primary' => $item['primary'] ?? false])
                   @if ($item['active']) aria-current="page" @endif>
                    <span class="tab-bar__icon"><x-icon :name="$item['icon']" :size="22" /></span>
                    <span class="tab-bar__label">{{ $item['label'] }}</span>
                    @if (isset($item['badge']) && $unread > 0)
                        <span class="count-badge count-badge--header">{{ $unread > 99 ? '99+' : $unread }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
