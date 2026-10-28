<?php

namespace App\Modules\Media;

/**
 * Liest Aufnahmedaten aus Drohnen-Panoramen (DJI „Sphäre“) und erzeugt eine Handy-Fassung.
 *
 * - GPS aus EXIF (benötigt die PHP-Erweiterung exif; fehlt sie, wird die Position auf der Karte gesetzt)
 * - Flughöhe über dem Startpunkt aus dem DJI-XMP-Block (drone-dji:RelativeAltitude)
 */
class PanoramaImage
{
    public const MOBILE_WIDTH = 4096;

    /**
     * @return array{lat: ?float, lng: ?float, relative_altitude: ?float, width: ?int, height: ?int, captured_at: ?string}
     */
    public static function metadata(string $absolutePath): array
    {
        $result = ['lat' => null, 'lng' => null, 'relative_altitude' => null, 'width' => null, 'height' => null, 'captured_at' => null];

        $size = @getimagesize($absolutePath);
        if ($size) {
            [$result['width'], $result['height']] = $size;
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($absolutePath, 'GPS,EXIF', true);
            $gps = $exif['GPS'] ?? null;
            if ($gps && isset($gps['GPSLatitude'], $gps['GPSLongitude'])) {
                $result['lat'] = self::toDegrees($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
                $result['lng'] = self::toDegrees($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');
            }
            $taken = $exif['EXIF']['DateTimeOriginal'] ?? null;
            if (is_string($taken) && preg_match('/^(\d{4}):(\d{2}):(\d{2}) (\d{2}:\d{2}:\d{2})$/', $taken, $m)) {
                $result['captured_at'] = "{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}";
            }
        }

        // DJI schreibt die Flughöhe als XMP-Text in die ersten Kilobytes der Datei
        $head = @file_get_contents($absolutePath, false, null, 0, 262144) ?: '';
        if (preg_match('/drone-dji:RelativeAltitude="([+-]?[0-9.]+)"/', $head, $m)
            || preg_match('/<drone-dji:RelativeAltitude>([+-]?[0-9.]+)</', $head, $m)) {
            $result['relative_altitude'] = (float) $m[1];
        }

        return $result;
    }

    /**
     * Erzeugt eine verkleinerte Fassung (Breite 4096) neben dem Original. Gibt den Pfad relativ zu public/ zurück
     * oder null, wenn nicht nötig oder nicht möglich (zu wenig Arbeitsspeicher auf dem Server).
     */
    public static function makeMobileVariant(string $relativePath): ?string
    {
        $absolute = public_path($relativePath);
        $size = @getimagesize($absolute);
        if (! $size || $size[0] <= self::MOBILE_WIDTH || ($size[2] ?? null) !== IMAGETYPE_JPEG || ! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        // 8K-Panorama braucht unkomprimiert rund 130 MB
        @ini_set('memory_limit', '768M');
        $source = @imagecreatefromjpeg($absolute);
        if (! $source) {
            return null;
        }

        $width = self::MOBILE_WIDTH;
        $height = (int) round($size[1] * $width / $size[0]);
        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        imageinterlace($target, true);

        $mobile = preg_replace('/\.jpe?g$/i', '', $relativePath).'-mobile.jpg';
        $ok = imagejpeg($target, public_path($mobile), 82);
        imagedestroy($source);
        imagedestroy($target);

        return $ok ? $mobile : null;
    }

    /** @param  array<int, string>  $parts  EXIF-Brüche wie "51/1", "40/1", "4467/100" */
    private static function toDegrees(array $parts, string $ref): ?float
    {
        $values = array_map(function ($part) {
            if (is_string($part) && str_contains($part, '/')) {
                [$a, $b] = explode('/', $part, 2);

                return (float) $b === 0.0 ? 0.0 : (float) $a / (float) $b;
            }

            return (float) $part;
        }, array_values($parts));

        if (count($values) < 3) {
            return null;
        }

        $degrees = $values[0] + $values[1] / 60 + $values[2] / 3600;

        return in_array(strtoupper($ref), ['S', 'W'], true) ? -$degrees : $degrees;
    }
}
