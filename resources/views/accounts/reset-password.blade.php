<x-auth-shell title="Neues Passwort">
    <h1>Neues Passwort festlegen</h1>

    <form method="post" action="{{ route('password.update') }}" class="form-stack">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="field">
            <span>E-Mail-Adresse</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
        </label>
        <label class="field">
            <span>Neues Passwort</span>
            <input type="password" name="password" required minlength="10" autocomplete="new-password" autofocus>
            <span class="field__hint">Mindestens 10 Zeichen, Buchstaben und Zahlen.</span>
        </label>
        <label class="field">
            <span>Passwort wiederholen</span>
            <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
        </label>
        <button class="button button--primary button--large button--block" type="submit">Passwort speichern</button>
    </form>
</x-auth-shell>
