@php($legal = config('legal'))
<x-layout title="Impressum">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Rechtliches</p>
            <h1 class="page-head__title">Impressum</h1>
        </div>
    </section>
    <section class="section section--tight">
        <div class="container prose">
            @if ($legal['draft'])
                <p class="draft-note">Entwurf – Angaben werden vor dem öffentlichen Start vervollständigt und rechtlich geprüft.</p>
            @endif

            <h2>Angaben gemäß § 5 DDG</h2>
            <address>
                {{ $legal['operator'] ?: 'Name / Firma – noch eintragen' }}<br>
                {{ $legal['street'] ?: 'Straße – noch eintragen' }}<br>
                {{ $legal['city'] ?: 'PLZ Ort – noch eintragen' }}<br>
                {{ $legal['country'] }}
            </address>

            <h2>Kontakt</h2>
            <p>
                E-Mail: @if ($legal['email'])<a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>@else noch eintragen @endif
                @if ($legal['phone'])<br>Telefon: {{ $legal['phone'] }}@endif
            </p>

            @if ($legal['vat_id'])
                <h2>Umsatzsteuer-ID</h2>
                <p>{{ $legal['vat_id'] }}</p>
            @endif
            @if ($legal['register'])
                <h2>Registereintrag</h2>
                <p>{{ $legal['register'] }}</p>
            @endif

            <h2>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h2>
            <p>{{ $legal['content_responsible'] ?: ($legal['operator'] ?: 'noch eintragen') }}</p>

            <h2>Bildrechte</h2>
            <p>Luftaufnahmen, 360°-Aufnahmen und Messdaten sind eigene Aufnahmen bzw. werden mit Zustimmung der jeweiligen Golfclubs veröffentlicht. Satellitenbilder im internen Redaktionsbereich: Esri, Maxar, Earthstar Geographics.</p>
        </div>
    </section>
</x-layout>
