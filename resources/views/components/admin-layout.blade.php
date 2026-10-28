@props(['title' => 'Admin', 'wide' => false])
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · Admin · My Golf Course</title>
    <link rel="icon" href="{{ asset_v('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset_v('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/community.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/admin.css') }}">
    {{ $head ?? '' }}
</head>
<body class="admin">
    <header class="admin-header">
        <a class="brand" href="{{ route('admin.dashboard') }}">
            <span class="brand__logo" aria-hidden="true"></span>
            <span class="brand__name">Admin</span>
        </a>
        @auth
            <nav class="admin-header__nav">
                <a href="{{ route('admin.dashboard') }}">Plätze</a>
                <a href="{{ route('admin.reports.index') }}">Meldungen</a>
                <a href="{{ route('home') }}" target="_blank" rel="noopener">Website ansehen ↗</a>
                <span class="muted">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Abmelden</button>
                </form>
            </nav>
        @endauth
    </header>

    <main @class(['admin-main', 'admin-main--wide' => $wide])>
        @if (session('status'))
            <p class="admin-flash">{{ session('status') }}</p>
        @endif
        @foreach ((array) session('warnings', []) as $warning)
            <p class="admin-flash admin-flash--warning">{{ $warning }}</p>
        @endforeach
        @if ($errors->any())
            <div class="admin-flash admin-flash--error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </main>

    {{ $scripts ?? '' }}
</body>
</html>
