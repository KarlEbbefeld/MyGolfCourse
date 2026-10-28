<?php

namespace App\Core\Support;

/**
 * Anzeige von Längen und Distanzen in Metern oder Yards (Nutzereinstellung, docs/DECISIONS.md D-012).
 * Höhen (Bäume) bleiben immer in Metern. Gespeichert wird ausschließlich in Metern.
 */
final class Units
{
    public const YARDS_PER_METER = 1.0936133;

    public static function current(): string
    {
        return auth()->user()?->units() ?? 'm';
    }

    /** Wert in der gewählten Einheit (gerundet), z. B. 352 bzw. 385. */
    public static function value(float $meters): int
    {
        return (int) round(self::current() === 'yd' ? $meters * self::YARDS_PER_METER : $meters);
    }

    /** „352 m“ bzw. „385 yd“ */
    public static function distance(?float $meters): string
    {
        if ($meters === null) {
            return '–';
        }

        return number_format(self::value($meters), 0, ',', '.').' '.self::current();
    }

    public static function label(): string
    {
        return self::current() === 'yd' ? 'Yards' : 'Meter';
    }
}
