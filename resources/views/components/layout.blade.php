@props([
    'title' => null,
    'description' => 'Golfplätze digital erkunden – mit Drohnenüberflug, 360°-Ansicht und vermessenen Hindernissen.',
    'bodyClass' => '',
    'overlayHeader' => false,
    'toast' => true,
])
@php
    $user = auth()->user();
    $initials = $user?->initials();
    // Zähler in der Kopfzeile (ungelesene Chats und Mitteilungen)
    $unreadMessages = $user ? \App\Modules\Community\Support\Inbox::unreadTotal($user) : 0;
    $unreadNotifications = $user ? $user->unreadNotifications()->count() : 0;
    // Menüleiste unten (Handy) nicht im Chat und auf Bahnseiten – dort liegt unten schon Eingabefeld bzw. Bahnleiste
    $hideTabBar = request()->routeIs('chats.show', 'holes.show');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · My Golf Course' : 'My Golf Course – Jede Bahn kennen, bevor du abschlägst' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#0b2219">
    <meta property="og:title" content="{{ $title ?? 'My Golf Course' }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset_v('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/instrument-sans-var.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset_v('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/community.css') }}">
    {{ $head ?? '' }}
</head>
<body @class([$bodyClass, 'has-tab-bar' => ! $hideTabBar])>
    <a class="skip-link" href="#inhalt">Zum Inhalt springen</a>

    <header @class(['site-header', 'site-header--overlay' => $overlayHeader]) @if ($overlayHeader) data-overlay-header @endif>
        <div class="site-header__inner">
            <a class="brand" href="{{ route('home') }}" aria-label="My Golf Course – Startseite">
                <img class="brand__image" src="{{ asset_v('images/logo.png') }}" alt="My Golf Course" width="1536" height="1024">
            </a>

            {{-- Computer: Hauptmenü ausgeschrieben links (wie im Golf Marketplace) --}}
            <nav class="site-nav" aria-label="Hauptnavigation">
                <a href="{{ route('home') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('home')])>Start</a>
                <a href="{{ route('courses.index') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('courses.*', 'holes.*', 'yardage-book', 'clubs.*')])>Golfplätze</a>
                @if ($user)
                    <a href="{{ route('community.index') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('community.*', 'players.*')])>Community</a>
                @endif
                <a href="{{ route('about') }}" @class(['site-nav__link', 'is-active' => request()->routeIs('about')])>So funktioniert’s</a>
            </nav>

            {{-- Symbole rechts mit Beschriftung; Zähler aktualisiert public/js/header-badges.js ohne Neuladen --}}
            <ul class="header-icons" @if ($user) data-badges-url="{{ route('notifications.counts') }}" @endif>
                <li class="header-icons__desktop">
                    <a href="{{ route('courses.index') }}" class="header-icon">
                        <x-icon name="search" :size="20" />
                        <span class="header-icon__label">Suchen</span>
                    </a>
                </li>
                @if ($user)
                    <li class="header-icons__desktop">
                        <a href="{{ route('chats.index') }}" data-badge="messages" @class(['header-icon', 'is-active' => request()->routeIs('chats.*')])>
                            <x-icon name="mail" :size="20" />
                            <span class="header-icon__label">Nachrichten</span>
                            @if ($unreadMessages > 0)<span class="count-badge count-badge--header">{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>@endif
                        </a>
                    </li>
                    {{-- Glocke nur bei ungelesenen Mitteilungen – dann bimmelt sie; sonst im Konto-Menü --}}
                    <li class="header-icons__desktop header-bell" data-badge-show="notifications" @if ($unreadNotifications <= 0) hidden @endif>
                        <a href="{{ route('notifications.index') }}" data-badge="notifications" class="header-icon">
                            <x-icon name="bell" :size="20" class="header-bell__icon" />
                            <span class="header-icon__label">Mitteilungen</span>
                            @if ($unreadNotifications > 0)<span class="count-badge count-badge--header">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>@endif
                        </a>
                    </li>
                    <li class="header-icons__desktop">
                        <a href="{{ route('account.show') }}#merkliste" class="header-icon">
                            <x-icon name="heart" :size="20" />
                            <span class="header-icon__label">Merkliste</span>
                        </a>
                    </li>
                    {{-- Konto ganz rechts: Profilkreis öffnet das Menü --}}
                    <li class="header-icons__desktop header-account">
                        <details class="header-account__menu" data-dropdown>
                            <summary @class(['header-icon', 'header-account__summary', 'is-active' => request()->routeIs('account.*', 'notifications.*')])>
                                <x-avatar :user="$user" size="sm" />
                                <span class="header-icon__label">Konto <span class="header-account__caret" aria-hidden="true"></span></span>
                                <span class="header-account__dot" data-badge-show="notifications" @if ($unreadNotifications <= 0) hidden @endif aria-hidden="true"></span>
                            </summary>
                            <div class="header-account__panel">
                                <a href="{{ route('account.show') }}" class="header-account__head">
                                    <x-avatar :user="$user" size="md" />
                                    <span><strong>{{ $user->name }}</strong><span class="muted">Mein Konto</span></span>
                                </a>
                                <ul class="header-account__list">
                                    <x-account-links :user="$user" :unread-notifications="$unreadNotifications" />
                                </ul>
                                <form method="post" action="{{ route('logout') }}" class="header-account__logout">
                                    @csrf
                                    <button type="submit" class="account-link"><x-icon name="logout" :size="18" /> Abmelden</button>
                                </form>
                            </div>
                        </details>
                    </li>
                @else
                    <li class="header-icons__desktop"><a href="{{ route('login') }}" class="header-icon"><x-icon name="user" :size="20" /><span class="header-icon__label">Anmelden</span></a></li>
                    <li class="header-icons__desktop"><a href="{{ route('register') }}" class="button button--primary header-register">Registrieren</a></li>
                @endif
            </ul>

            {{-- Handy: Burger-Menü mit Konto und Seltenem; Start, Plätze, Community, Nachrichten, Merkliste stehen unten in der Leiste --}}
            <details class="mobile-menu" data-dropdown>
                <summary class="mobile-menu__toggle">
                    <span class="mobile-menu__bars" aria-hidden="true"></span>
                    <span class="visually-hidden">Menü</span>
                    @if ($user)
                        <span class="mobile-menu__alert" data-badge-show="notifications" @if ($unreadNotifications <= 0) hidden @endif aria-hidden="true"><x-icon name="bell" :size="14" /></span>
                    @endif
                </summary>
                <div class="mobile-menu__panel">
                    @if ($user)
                        <a href="{{ route('account.show') }}" class="header-account__head">
                            <x-avatar :user="$user" size="md" />
                            <span><strong>{{ $user->name }}</strong><span class="muted">Mein Konto</span></span>
                        </a>
                        <ul class="header-account__list">
                            <x-account-links :user="$user" :unread-notifications="$unreadNotifications" />
                        </ul>
                        <form method="post" action="{{ route('logout') }}" class="header-account__logout">
                            @csrf
                            <button type="submit" class="account-link"><x-icon name="logout" :size="18" /> Abmelden</button>
                        </form>
                    @else
                        <ul class="header-account__list">
                            <li><a href="{{ route('login') }}" class="account-link"><x-icon name="user" :size="18" /> Anmelden</a></li>
                            <li><a href="{{ route('courses.index') }}" class="account-link"><x-icon name="flag" :size="18" /> Golfplätze</a></li>
                            <li><a href="{{ route('about') }}" class="account-link"><x-icon name="help" :size="18" /> So funktioniert’s</a></li>
                        </ul>
                        <a href="{{ route('register') }}" class="button button--primary button--block">Kostenlos registrieren</a>
                    @endif
                </div>
            </details>
        </div>
    </header>

    @if ($toast && session('status'))
        <div class="toast" role="status">{{ session('status') }}</div>
    @endif

    <main id="inhalt" class="site-main">
        {{ $slot }}
    </main>

    @unless ($hideTabBar)
        <x-tab-bar :user="$user" :unread="$unreadMessages" />
    @endunless

    @unless ($attributes->has('no-footer'))
        <footer class="site-footer">
            <div class="container">
                <div class="site-footer__grid">
                    <div class="site-footer__brand">
                        <a class="brand" href="{{ route('home') }}">
                            <img class="brand__image brand__image--footer" src="{{ asset_v('images/logo.png') }}" alt="My Golf Course" width="1536" height="1024" loading="lazy">
                        </a>
                        <p>Golfplätze digital erkunden – mit Drohnenüberflug, 360°-Ansicht und vermessenen Hindernissen. Für eine Runde ohne Überraschungen.</p>
                    </div>
                    <div>
                        <p class="site-footer__title">Entdecken</p>
                        <ul class="site-footer__list">
                            <li><a href="{{ route('courses.index') }}">Alle Golfplätze</a></li>
                            <li><a href="{{ route('about') }}">So funktioniert’s</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="site-footer__title">Konto</p>
                        <ul class="site-footer__list">
                            @if ($user)
                                <li><a href="{{ route('account.show') }}">Mein Konto</a></li>
                                <li><a href="{{ route('community.index') }}">Community</a></li>
                                <li><a href="{{ route('chats.index') }}">Nachrichten</a></li>
                                <li><a href="{{ route('account.settings') }}">Einstellungen</a></li>
                            @else
                                <li><a href="{{ route('login') }}">Anmelden</a></li>
                                <li><a href="{{ route('register') }}">Konto erstellen</a></li>
                            @endif
                        </ul>
                    </div>
                    <div>
                        <p class="site-footer__title">Rechtliches</p>
                        <ul class="site-footer__list">
                            <li><a href="{{ route('imprint') }}">Impressum</a></li>
                            <li><a href="{{ route('privacy') }}">Datenschutz</a></li>
                        </ul>
                    </div>
                </div>
                <div class="site-footer__bottom">
                    <span>© {{ date('Y') }} My Golf Course</span>
                    @unless (app()->isProduction())
                        <span>Testversion · Inhalte teilweise Demodaten</span>
                    @endunless
                </div>
            </div>
        </footer>
    @endunless

    <script src="{{ asset_v('js/app.js') }}" defer></script>
    @if ($user)
        <script src="{{ asset_v('js/header-badges.js') }}" defer></script>
    @endif
    {{ $scripts ?? '' }}
</body>
</html>
