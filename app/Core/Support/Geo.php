<?php

namespace App\Core\Support;

/**
 * Geo-Helfer auf der Kugel (WGS84, Erdradius 6.371 km). Für Golfbahnen (< 1 km) mehr als genau genug.
 * Koordinaten immer als [lat, lng] in Grad, Distanzen in Metern, Peilung in Grad ab Norden im Uhrzeigersinn.
 */
final class Geo
{
    public const EARTH_RADIUS_M = 6371000.0;

    /** Großkreis-Distanz (Haversine) in Metern. */
    public static function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dPhi = deg2rad($lat2 - $lat1);
        $dLambda = deg2rad($lng2 - $lng1);

        $a = sin($dPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dLambda / 2) ** 2;

        return 2 * self::EARTH_RADIUS_M * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Anfangspeilung von Punkt 1 zu Punkt 2 in Grad (0 = Norden, 90 = Osten). */
    public static function bearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dLambda = deg2rad($lng2 - $lng1);

        $y = sin($dLambda) * cos($phi2);
        $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($dLambda);

        return fmod(rad2deg(atan2($y, $x)) + 360, 360);
    }

    /**
     * Zielpunkt ausgehend von einem Startpunkt, einer Peilung und einer Distanz.
     *
     * @return array{0: float, 1: float} [lat, lng]
     */
    public static function destination(float $lat, float $lng, float $bearingDeg, float $distanceM): array
    {
        $delta = $distanceM / self::EARTH_RADIUS_M;
        $theta = deg2rad($bearingDeg);
        $phi1 = deg2rad($lat);
        $lambda1 = deg2rad($lng);

        $phi2 = asin(sin($phi1) * cos($delta) + cos($phi1) * sin($delta) * cos($theta));
        $lambda2 = $lambda1 + atan2(sin($theta) * sin($delta) * cos($phi1), cos($delta) - sin($phi1) * sin($phi2));

        return [rad2deg($phi2), fmod(rad2deg($lambda2) + 540, 360) - 180];
    }
}
