<x-auth-shell title="Passwort vergessen">
    <h1>Passwort vergessen</h1>
    <p class="muted">Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegst.</p>

    <form method="post" action="{{ route('password.email') }}" class="form-stack">
        @csrf
        <label class="field">
            <span>E-Mail-Adresse</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </label>
        <button class="button button--primary button--large button--block" type="submit">Link anfordern</button>
    </form>

    <p class="auth__switch"><a href="{{ route('login') }}">Zurück zur Anmeldung</a></p>
</x-auth-shell>
