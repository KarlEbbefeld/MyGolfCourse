<?php

namespace App\Modules\Media;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Kodiert hochgeladene Bilder mit GD neu (übernommen aus dem Golf Marketplace):
 * - entfernt EXIF/Metadaten (z. B. GPS-Standort),
 * - neutralisiert manipulierte Dateien (nur echte Pixeldaten bleiben),
 * - begrenzt die Kantenlänge und speichert einheitlich als JPEG.
 */
class ImageProcessor
{
    public const MAX_EDGE = 1600;

    public const QUALITY = 82;

    /**
     * @return array{data: string, width: int, height: int}
     */
    public function toJpeg(UploadedFile $file): array
    {
        $source = $this->load($file);

        [$width, $height] = $this->fitWithin(imagesx($source), imagesy($source));

        return $this->encode($source, 0, 0, imagesx($source), imagesy($source), $width, $height);
    }

    /**
     * Quadratischer Ausschnitt aus der Bildmitte (Profilbild), gleiche Bereinigung wie toJpeg().
     *
     * @return array{data: string, width: int, height: int}
     */
    public function toSquareJpeg(UploadedFile $file, int $size = 400): array
    {
        $source = $this->load($file);

        $edge = min(imagesx($source), imagesy($source));
        $x = intdiv(imagesx($source) - $edge, 2);
        $y = intdiv(imagesy($source) - $edge, 2);
        $size = min($size, $edge);

        return $this->encode($source, $x, $y, $edge, $edge, $size, $size);
    }

    /**
     * Verkleinerte Fassung eines bereits gespeicherten (bereinigten) JPEGs; längste Kante $edge.
     * Gibt null zurück, wenn GD die Datei nicht lesen kann – dann wird das Original ausgeliefert.
     *
     * @return array{data: string, width: int, height: int}|null
     */
    public function shrinkJpeg(string $absolutePath, int $edge, int $quality = 78): ?array
    {
        $source = @imagecreatefromjpeg($absolutePath);
        if ($source === false) {
            return null;
        }

        $scale = min(1, $edge / max(imagesx($source), imagesy($source)));
        $width = max(1, (int) round(imagesx($source) * $scale));
        $height = max(1, (int) round(imagesy($source) * $scale));

        $canvas = imagecreatetruecolor($width, $height);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));
        // Progressives JPEG: auf langsamen Verbindungen erscheint sofort eine grobe Fassung
        imageinterlace($canvas, true);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $data = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return ['data' => $data, 'width' => $width, 'height' => $height];
    }

    private function load(UploadedFile $file): \GdImage
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false) {
            throw new RuntimeException('Keine gültige Bilddatei.');
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if ($source === false) {
            throw new RuntimeException('Bildformat wird nicht unterstützt.');
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $source = $this->applyExifOrientation($source, $file->getRealPath());
        }

        return $source;
    }

    /**
     * Ausschnitt ($srcX, $srcY, $srcW, $srcH) auf $width × $height neu als JPEG kodieren.
     *
     * @return array{data: string, width: int, height: int}
     */
    private function encode(\GdImage $source, int $srcX, int $srcY, int $srcW, int $srcH, int $width, int $height): array
    {
        $canvas = imagecreatetruecolor($width, $height);
        // Transparenz (PNG/WebP) auf weißen Hintergrund legen
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $width, $height, $srcW, $srcH);
        imageinterlace($canvas, true);

        ob_start();
        imagejpeg($canvas, null, self::QUALITY);
        $data = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return ['data' => $data, 'width' => $width, 'height' => $height];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fitWithin(int $width, int $height): array
    {
        $scale = min(1, self::MAX_EDGE / max($width, $height));

        return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
    }

    /**
     * Handyfotos sind oft nur per EXIF gedreht; da EXIF entfernt wird, Drehung vorher anwenden.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated === false ? $image : $rotated;
    }
}
