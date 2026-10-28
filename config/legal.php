<?php

/*
 * Angaben für Impressum und Datenschutzerklärung. Echte Werte nur in der Server-.env eintragen.
 * Fehlende Pflichtangaben erscheinen auf den Seiten deutlich als „noch eintragen“.
 */
return [
    // Anbieter nach § 5 DDG (Name bzw. Firma, ladungsfähige Anschrift, Kontakt)
    'operator' => env('LEGAL_OPERATOR'),
    'street' => env('LEGAL_STREET'),
    'city' => env('LEGAL_CITY'),
    'country' => env('LEGAL_COUNTRY', 'Deutschland'),
    'email' => env('LEGAL_EMAIL'),
    'phone' => env('LEGAL_PHONE'),
    // Optional: Umsatzsteuer-ID, Registereintrag (nur falls vorhanden)
    'vat_id' => env('LEGAL_VAT_ID'),
    'register' => env('LEGAL_REGISTER'),
    // Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV (falls abweichend vom Anbieter)
    'content_responsible' => env('LEGAL_CONTENT_RESPONSIBLE'),

    // Hosting-Anbieter für die Datenschutzerklärung
    'hosting' => env('LEGAL_HOSTING', 'Hostinger International Ltd. (Zypern, EU)'),

    // Stand der Datenschutzerklärung und Hinweis „Entwurf, rechtlich prüfen lassen“
    'privacy_updated' => env('LEGAL_PRIVACY_UPDATED', '28.10.2026'),
    'draft' => (bool) env('LEGAL_DRAFT', true),
];
