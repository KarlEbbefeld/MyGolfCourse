<x-auth-shell title="Konto erstellen" aside-title="Kostenlos und in einer Minute." aside-text="Die Plätze kannst du auch ohne Konto erkunden. Mit Konto merkst du dir Plätze und stellst Meter oder Yards ein.">
    <h1>Konto erstellen</h1>
    <p class="muted">Kostenlos. Wir schicken keine Werbung.</p>

    <form method="post" action="{{ route('register.store') }}" class="form-stack">
        @csrf
        <label class="field">
            <span>Name</span>
            <input name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" autofocus>
        </label>
        <label class="field">
            <span>E-Mail-Adresse</span>
            <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        </label>
        <label class="field">
            <span>Passwort</span>
            <input type="password" name="password" required minlength="10" autocomplete="new-password">
            <span class="field__hint">Mindestens 10 Zeichen, Buchstaben und Zahlen.</span>
        </label>
        <label class="field">
            <span>Passwort wiederholen</span>
            <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
        </label>
        <label class="check">
            <input type="checkbox" name="profile_public" value="1" @checked(old('profile_public'))>
            <span>Mein Spielerprofil öffentlich zeigen (Name, Club, Handicap). Jederzeit änderbar – sonst sehen es nur Freunde.</span>
        </label>
        <label class="check">
            <input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))>
            <span>Ich habe die <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen.</span>
        </label>
        <button class="button button--primary button--large button--block" type="submit">Konto erstellen</button>
    </form>

    <p class="auth__switch">Schon registriert? <a href="{{ route('login') }}">Anmelden</a></p>
</x-auth-shell>
