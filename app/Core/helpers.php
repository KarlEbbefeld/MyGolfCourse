<?php

if (! function_exists('asset_v')) {
    /**
     * URL zu einer Datei in public/ mit Änderungszeit als Versionsnummer.
     * Kein Build-Schritt nötig: Nach dem Hochladen sehen Browser sofort die neue Fassung.
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}

if (! function_exists('distance')) {
    /** Länge/Distanz in der Einheit des Nutzers: „352 m“ bzw. „385 yd“ (Höhen weiter mit meters()). */
    function distance(?float $meters): string
    {
        return App\Core\Support\Units::distance($meters);
    }
}

if (! function_exists('meters')) {
    /** 352 m, 0,5 m – deutsche Schreibweise. */
    function meters(?float $value): string
    {
        if ($value === null) {
            return '–';
        }

        return App\Modules\Hazards\Models\Measurement::meters($value);
    }
}
