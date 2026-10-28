<?php

namespace App\Modules\Media;

use App\Modules\Courses\Models\Hole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Ablage hochgeladener Medien unter public/media/courses/<platz>/bahn-<nr>/ (öffentlich auslieferbar).
 * Gespeichert wird der Pfad relativ zu public/, ausgeliefert mit asset_v().
 * Später ersetzbar durch einen Medienanbieter mit CDN (docs/DECISIONS.md D-007) – nur diese Klasse ändert sich.
 */
class MediaStore
{
    public static function holeDirectory(Hole $hole): string
    {
        return 'media/courses/'.$hole->course_id.'/bahn-'.$hole->number;
    }

    /** Speichert die Datei mit eindeutigem Namen und gibt den Pfad relativ zu public/ zurück. */
    public static function store(UploadedFile $file, string $directory, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $name = $prefix.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.'.$extension;

        $absolute = public_path($directory);
        if (! is_dir($absolute)) {
            mkdir($absolute, 0755, true);
        }

        $file->move($absolute, $name);
        @chmod($absolute.'/'.$name, 0644);

        return $directory.'/'.$name;
    }

    /** Löscht eine Datei, aber nur innerhalb von public/media (Schutz vor falschen Pfaden). */
    public static function delete(?string $path): void
    {
        if (! $path || ! Str::startsWith($path, 'media/') || Str::contains($path, '..')) {
            return;
        }

        $absolute = public_path($path);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /** PHP-Grenzen für Uploads – im Admin angezeigt, weil große Videos/Panoramen daran scheitern können. */
    public static function uploadLimit(): string
    {
        return 'upload_max_filesize '.ini_get('upload_max_filesize').' · post_max_size '.ini_get('post_max_size');
    }
}
