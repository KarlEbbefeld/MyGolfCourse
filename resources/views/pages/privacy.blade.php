@php($legal = config('legal'))
<x-layout title="Datenschutz">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Rechtliches</p>
            <h1 class="page-head__title">Datenschutzerklärung</h1>
        </div>
    </section>
    <section class="section section--tight">
        <div class="container prose">
            @if ($legal['draft'])
                <p class="draft-note">Entwurf – vor dem öffentlichen Start rechtlich prüfen lassen.</p>
            @endif

            <h2>1. Verantwortlicher</h2>
            <p>{{ $legal['operator'] ?: 'Name / Firma – noch eintragen' }}, {{ $legal['street'] ?: 'Straße' }}, {{ $legal['city'] ?: 'PLZ Ort' }}.
                E-Mail: {{ $legal['email'] ?: 'noch eintragen' }}</p>

            <h2>2. Grundsätze</h2>
            <p>Wir erheben so wenige Daten wie möglich. Es gibt keine Werbe- oder Analyse-Tracker und keine Einbindung externer Schriften oder Dienste beim Besuch der Website. Alle Schriften, Bibliotheken und Medien werden von unserem eigenen Server ausgeliefert.</p>

            <h2>3. Besuch der Website</h2>
            <p>Beim Aufruf verarbeitet der Server technisch notwendige Daten (IP-Adresse, Zeitpunkt, aufgerufene Seite, Browser) zur Auslieferung und Absicherung der Website (Art. 6 Abs. 1 lit. f DSGVO). Server-Protokolle werden nach höchstens 14 Tagen gelöscht.</p>

            <h2>4. Cookies und Sitzung</h2>
            <p>Wir verwenden nur technisch notwendige Cookies: ein Sitzungs-Cookie (Anmeldung, zuletzt angesehene Plätze für Gäste, Schutz vor Formular-Missbrauch). Es wird nach 2 Stunden Inaktivität bzw. beim Schließen ungültig. „Angemeldet bleiben“ setzt ein zusätzliches Cookie, das du durch Abmelden entfernst.</p>

            <h2>5. Konto</h2>
            <p>Für ein Konto speichern wir Name, E-Mail-Adresse, ein verschlüsseltes Passwort, deine Einstellungen (Einheit, bevorzugter Abschlag), deine Merkliste und die zuletzt angesehenen Plätze (höchstens 20). Rechtsgrundlage ist die Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO). Unter <em>Konto → Einstellungen</em> kannst du alle Daten herunterladen und dein Konto jederzeit sofort löschen.</p>

            <h2>5a. Community: Profil, Club, Folgen und Nachrichten</h2>
            <p>Dein Spielerprofil (Name, Nutzername, optional Bio, Handicap und Club) ist standardmäßig <strong>privat</strong>: Andere sehen dann nur Name und Nutzername; Details sehen nur Freunde (gegenseitiges Folgen). Schaltest du dein Profil öffentlich, können angemeldete Golfer es finden und dir schreiben.</p>
            <p>Ein Profilbild ist freiwillig. Beim Hochladen speichern wir es neu und entfernen dabei eingebettete Metadaten wie den Aufnahmeort (GPS). Bei privatem Profil sehen es nur Freunde. Löschen kannst du es jederzeit in den Einstellungen; mit dem Konto wird es automatisch gelöscht.</p>
            <p>Nachrichten speichern wir, damit die Empfänger sie lesen können: Direktnachrichten sehen nur die beiden Beteiligten, Nachrichten im Club-Chat alle Mitglieder des Clubs. Löschst du dein Konto, bleiben deine Nachrichten für die anderen erhalten, erscheinen aber als „Gelöschtes Konto“. Blockierte Personen sehen dein Profil und deine Nachrichten nicht mehr.</p>
            <p>Meldungen zu Personen oder Nachrichten prüft unser Team; dazu sehen wir die gemeldete Nachricht. Rechtsgrundlage: Art. 6 Abs. 1 lit. b und f DSGVO (Betrieb der Community, Schutz vor Missbrauch).</p>

            <h2>6. E-Mails</h2>
            <p>Wir senden nur E-Mails, die du auslöst, z. B. zum Zurücksetzen des Passworts. Keine Newsletter.</p>

            <h2>7. Hosting</h2>
            <p>Die Website wird gehostet bei {{ $legal['hosting'] }}. Mit dem Anbieter besteht ein Vertrag zur Auftragsverarbeitung.</p>

            <h2>8. Aufnahmen von Golfplätzen</h2>
            <p>Wir achten darauf, dass auf Aufnahmen keine Personen oder Kfz-Kennzeichen erkennbar sind. Solltest du dich dennoch erkennen, schreib uns – wir machen die Stelle umgehend unkenntlich.</p>

            <h2>9. Deine Rechte</h2>
            <p>Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und Widerspruch sowie das Recht auf Beschwerde bei einer Datenschutz-Aufsichtsbehörde.</p>

            <p class="muted">Stand: {{ $legal['privacy_updated'] }}</p>
        </div>
    </section>
</x-layout>
