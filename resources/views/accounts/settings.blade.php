@php
    $units = old('units', $user->units());
    $teeColor = old('default_tee_color', $user->setting('default_tee_color'));
@endphp
<x-layout title="Einstellungen">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Mein Konto</p>
            <h1 class="page-head__title">Einstellungen</h1>
            <nav class="account-nav" aria-label="Kontobereiche">
                <a href="{{ route('account.show') }}">Übersicht</a>
                <a href="{{ route('account.settings') }}" class="is-active">Einstellungen</a>
                @if ($user->role->canEditContent())
                    <a href="{{ route('admin.dashboard') }}">Admin-Bereich</a>
                @endif
            </nav>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($errors->any())
                <div class="error-box" role="alert" style="margin-bottom: var(--space-6)">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="panel">
                <h2>Spielerprofil und Anzeige</h2>
                <p class="muted"><a href="{{ route('players.show', $user->username) }}">Mein Profil ansehen</a></p>

                {{-- Profilbild: eigenes Formular (Datei-Upload), steht deshalb vor dem Profilformular --}}
                <div class="avatar-editor">
                    <x-avatar :user="$user" size="xl" />
                    <div class="avatar-editor__actions">
                        <form method="post" action="{{ route('avatar.update') }}" enctype="multipart/form-data">
                            @csrf
                            <label class="visually-hidden" for="avatar-file">Profilbild</label>
                            <input id="avatar-file" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                            <button class="button button--secondary" type="submit">{{ $user->avatar_path ? 'Bild ersetzen' : 'Profilbild hochladen' }}</button>
                        </form>
                        @if ($user->avatar_path)
                            <form method="post" action="{{ route('avatar.destroy') }}">
                                @csrf
                                @method('delete')
                                <button class="link-button link-button--muted" type="submit">Profilbild entfernen</button>
                            </form>
                        @endif
                        <span class="field__hint">JPG, PNG oder WebP, max. 8 MB. Wird quadratisch zugeschnitten; Standortdaten aus dem Foto werden entfernt. Bei privatem Profil sehen es nur Freunde.</span>
                    </div>
                </div>
                <form method="post" action="{{ route('account.settings.update') }}" class="form-stack">
                    @csrf
                    @method('put')
                    <div class="form-row">
                        <label class="field"><span>Name</span>
                            <input name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name"></label>
                        <label class="field"><span>E-Mail-Adresse</span>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email"></label>
                    </div>
                    <div class="form-row">
                        <label class="field"><span>Nutzername</span>
                            <input name="username" value="{{ old('username', $user->username) }}" required minlength="3" maxlength="40" pattern="[a-z0-9][a-z0-9\-]*" autocapitalize="none">
                            <span class="field__hint">Dein Profil: {{ url('/spieler') }}/{{ $user->username }}</span></label>
                        <label class="field"><span>Handicap (optional)</span>
                            <input type="number" name="handicap" step="0.1" min="-10" max="54" value="{{ old('handicap', $user->handicap) }}" inputmode="decimal"></label>
                    </div>
                    <label class="field"><span>Über mich (optional)</span>
                        <textarea name="bio" rows="3" maxlength="500" placeholder="z. B. Spiele seit 10 Jahren, am liebsten früh morgens.">{{ old('bio', $user->bio) }}</textarea></label>
                    <label class="check">
                        <input type="checkbox" name="profile_public" value="1" @checked(old('profile_public', $user->profile_public))>
                        <span><strong>Profil öffentlich</strong> – andere Golfer finden dich, sehen Club, Handicap und gespielte Plätze und können dir schreiben. Ohne Haken sehen das nur deine Freunde (gegenseitiges Folgen).</span>
                    </label>
                    <div class="field">
                        <span>Längen anzeigen in</span>
                        <div class="segmented">
                            <label><input type="radio" name="units" value="m" @checked($units === 'm')><span>Meter</span></label>
                            <label><input type="radio" name="units" value="yd" @checked($units === 'yd')><span>Yards</span></label>
                        </div>
                    </div>
                    <label class="field"><span>Bevorzugter Abschlag</span>
                        <select name="default_tee_color">
                            <option value="">Wie vom Platz vorgegeben</option>
                            @foreach ($teeColors as $color)
                                <option value="{{ $color->value }}" @selected($teeColor === $color->value)>{{ $color->label() }}</option>
                            @endforeach
                        </select>
                        <span class="field__hint">Wird auf den Bahnseiten vorausgewählt, wenn der Platz diese Farbe hat.</span>
                    </label>
                    <div><button class="button button--primary" type="submit">Speichern</button></div>
                </form>
            </div>

            <div class="panel" id="bag">
                <h2>Mein Bag</h2>
                <p class="muted">Deine durchschnittliche <strong>Carry-Distanz</strong> je Schläger in {{ $unit === 'yd' ? 'Yards' : 'Metern' }}. Daraus entstehen dein Spielplan auf jeder Bahn und die Hinweise, welche Hindernisse du erreichst. Leer lassen, was du nicht spielst.</p>
                <form method="post" action="{{ route('bag.update') }}">
                    @csrf
                    @method('put')
                    <div class="bag-grid">
                        @foreach ($clubs as $key => $label)
                            <label class="field bag-grid__item"><span>{{ $label }}</span>
                                <span class="input-unit">
                                    <input type="number" name="bag[{{ $key }}]" min="5" max="400" step="1" inputmode="numeric"
                                           value="{{ old('bag.'.$key, $bagValues[$key] ?? '') }}">
                                    <span class="input-unit__label">{{ $unit }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div style="margin-top: var(--space-5)"><button class="button button--primary" type="submit">Bag speichern</button></div>
                </form>
            </div>

            <div class="panel">
                <h2>Passwort ändern</h2>
                <form method="post" action="{{ route('account.password') }}" class="form-stack">
                    @csrf
                    @method('put')
                    <label class="field"><span>Aktuelles Passwort</span>
                        <input type="password" name="current_password" required autocomplete="current-password"></label>
                    <div class="form-row">
                        <label class="field"><span>Neues Passwort</span>
                            <input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
                        <label class="field"><span>Wiederholen</span>
                            <input type="password" name="password_confirmation" required minlength="10" autocomplete="new-password"></label>
                    </div>
                    <div><button class="button button--secondary" type="submit">Passwort ändern</button></div>
                </form>
            </div>

            <div class="panel">
                <h2>Meine Daten</h2>
                <p class="muted">Alle gespeicherten Daten zu deinem Konto als Datei (Auskunft nach Art. 15 DSGVO).</p>
                <a class="button button--secondary" href="{{ route('account.export') }}">Daten herunterladen</a>
            </div>

            <div class="panel panel--danger">
                <h2>Konto löschen</h2>
                <p class="muted">Löscht dein Konto, deine Merkliste und deinen Verlauf sofort und endgültig.</p>
                <form method="post" action="{{ route('account.destroy') }}" class="form-stack" onsubmit="return confirm('Konto wirklich endgültig löschen?')">
                    @csrf
                    @method('delete')
                    <label class="field"><span>Zur Bestätigung dein Passwort</span>
                        <input type="password" name="password" required autocomplete="current-password"></label>
                    <div><button class="button button--danger" type="submit">Konto endgültig löschen</button></div>
                </form>
            </div>
        </div>
    </section>
</x-layout>
