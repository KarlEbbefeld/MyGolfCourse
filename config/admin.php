<?php

/*
 * Admin-Bereich (P018, P048–P050): Zugang, Zwei-Faktor per E-Mail, Sitzungsdauer.
 */
return [
    // Nur diese Konten können überhaupt Administrator sein (Server-.env, kommagetrennt, Groß/klein egal).
    // Leer: in local/testing genügt die Rolle, in Staging/Produktion ist dann niemand Admin.
    'emails' => array_values(array_filter(array_map(
        fn (string $email) => mb_strtolower(trim($email)),
        explode(',', (string) env('ADMIN_EMAILS', '')),
    ))),

    'mfa' => [
        // Nur in Tests abschaltbar (phpunit.xml: ADMIN_MFA=false); MFA-Tests schalten es gezielt ein
        'enabled' => (bool) env('ADMIN_MFA', true),
        // Code per E-Mail: Gültigkeit, Fehlversuche, wie lange eine Bestätigung trägt
        'code_ttl_minutes' => 10,
        'max_attempts' => 5,
        'valid_hours' => 12,
    ],

    // Ohne Aktivität im Admin-Bereich wird nach dieser Zeit erneut ein Code verlangt
    'idle_timeout_minutes' => (int) env('ADMIN_IDLE_TIMEOUT', 30),
];
