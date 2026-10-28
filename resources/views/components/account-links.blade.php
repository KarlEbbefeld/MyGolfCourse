@props(['user', 'unreadNotifications' => 0])
{{-- Konto-Einträge: gleich im Konto-Menü (Computer) und im Burger-Menü (Handy) – wie im Golf Marketplace --}}
<li>
    <a href="{{ route('notifications.index') }}" data-badge="notifications" @class(['account-link', 'is-active' => request()->routeIs('notifications.*')])>
        <x-icon name="bell" :size="18" /> Mitteilungen
        @if ($unreadNotifications > 0)<span class="count-badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>@endif
    </a>
</li>
<li><a href="{{ route('players.show', $user->username) }}" @class(['account-link', 'is-active' => request()->routeIs('players.show') && request()->route('user')?->is($user)])><x-icon name="user" :size="18" /> Mein Profil</a></li>
<li><a href="{{ route('community.index') }}" @class(['account-link', 'is-active' => request()->routeIs('community.*')])><x-icon name="sphere" :size="18" /> Community</a></li>
<li><a href="{{ route('account.show') }}#merkliste" class="account-link"><x-icon name="heart" :size="18" /> Merkliste</a></li>
<li><a href="{{ route('account.show') }}" @class(['account-link', 'is-active' => request()->routeIs('account.show')])><x-icon name="flag" :size="18" /> Runden &amp; Notizen</a></li>
<li><a href="{{ route('account.settings') }}#bag" class="account-link"><x-icon name="ruler" :size="18" /> Mein Bag</a></li>
<li><a href="{{ route('account.settings') }}" @class(['account-link', 'is-active' => request()->routeIs('account.settings')])><x-icon name="gear" :size="18" /> Einstellungen</a></li>
<li><a href="{{ route('about') }}" @class(['account-link', 'is-active' => request()->routeIs('about')])><x-icon name="help" :size="18" /> Hilfe</a></li>
@if ($user->role->canEditContent())
    <li><a href="{{ route('admin.dashboard') }}" class="account-link"><x-icon name="shield" :size="18" /> Admin-Bereich</a></li>
@endif
