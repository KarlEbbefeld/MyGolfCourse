<?php

namespace App\Modules\Courses\Support;

use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Hole;

/**
 * Übersichtsgrafik eines Platzes aus echten Koordinaten: jede Bahn als Linie Abschlag → Grün.
 * Ersetzt fehlende Luftbilder auf Karten und Kopfbereichen (resources/views/components/course-diagram.blade.php).
 * Erwartet geladene holes.tees.
 */
class CourseDiagram
{
    public const WIDTH = 1000;

    /**
     * @return array{width: int, height: int, holes: list<array{number: int, x1: float, y1: float, x2: float, y2: float}>}|null
     */
    public static function for(Course $course, float $aspect = 0.625): ?array
    {
        $segments = [];
        foreach ($course->holes as $hole) {
            /** @var Hole $hole */
            $tee = $hole->tees->first(fn ($t) => $t->hasPosition());
            if ($tee && $hole->hasGreenPosition()) {
                $segments[] = [$hole->number, $tee->lat, $tee->lng, $hole->green_lat, $hole->green_lng];
            }
        }

        if ($segments === []) {
            return null;
        }

        // Lokale ebene Projektion: Längengrade mit cos(Breite) stauchen, damit Proportionen stimmen
        $lats = array_merge(array_column($segments, 1), array_column($segments, 3));
        $lngs = array_merge(array_column($segments, 2), array_column($segments, 4));
        $cos = cos(deg2rad(array_sum($lats) / count($lats)));
        $minX = min($lngs) * $cos;
        $maxX = max($lngs) * $cos;
        $minY = -max($lats);
        $maxY = -min($lats);

        $width = self::WIDTH;
        $height = (int) round($width * $aspect);
        $padding = 0.12;
        $spanX = max($maxX - $minX, 1e-6);
        $spanY = max($maxY - $minY, 1e-6);
        $scale = min($width * (1 - 2 * $padding) / $spanX, $height * (1 - 2 * $padding) / $spanY);
        $offsetX = ($width - $spanX * $scale) / 2;
        $offsetY = ($height - $spanY * $scale) / 2;

        $project = fn (float $lat, float $lng) => [
            round($offsetX + ($lng * $cos - $minX) * $scale, 1),
            round($offsetY + (-$lat - $minY) * $scale, 1),
        ];

        return [
            'width' => $width,
            'height' => $height,
            'holes' => array_map(function ($s) use ($project) {
                [$x1, $y1] = $project($s[1], $s[2]);
                [$x2, $y2] = $project($s[3], $s[4]);

                return ['number' => $s[0], 'x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' => $y2];
            }, $segments),
        ];
    }
}
