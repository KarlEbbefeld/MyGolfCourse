<x-auth-shell title="Anmelden">
    <h1>Willkommen zurück</h1>
    <p class="muted">Melde dich an, um deine Merkliste und Einstellungen zu nutzen.</p>

    <form method="post" action="{{ route('login.store') }}" class="form-stack">
        @csrf
        <label class="field">
            <span>E-Mail-Adresse</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>
        <label class="field">
            <span>Passwort</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap">
            <label class="check"><input type="checkbox" name="remember" value="1"> Angemeldet bleiben</label>
            <a href="{{ route('password.request') }}" style="font-size: var(--text-sm)">Passwort vergessen?</a>
        </div>
        <button class="button button--primary button--large button--block" type="submit">Anmelden</button>
    </form>

    <p class="auth__switch">Noch kein Konto? <a href="{{ route('register') }}">Kostenlos registrieren</a></p>
</x-auth-shell>
