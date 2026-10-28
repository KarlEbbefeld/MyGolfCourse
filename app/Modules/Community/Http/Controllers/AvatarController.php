<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Media\ImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Profilbild (Muster aus dem Golf Marketplace): echte Bilddatei prüfen, Handy-Drehung anwenden,
 * Metadaten inkl. GPS entfernen, quadratisch zuschneiden, neu als JPEG speichern – privat abgelegt.
 * Ausgeliefert nur, wenn das Profil für den Betrachter sichtbar ist (öffentlich oder Freunde).
 */
class AvatarController extends Controller
{
    public const MAX_KB = 8192;

    public const SIZE = 400;

    public function show(Request $request, User $user): Response
    {
        // Team (Redaktion/Admin) sieht Bilder auch privater Profile – für die Prüfung von Meldungen
        $isTeam = $request->user()?->role->canEditContent() ?? false;
        abort_if($user->avatar_path === null || (! $isTeam && ! $user->isVisibleTo($request->user())), 404);

        $disk = Storage::disk(User::AVATAR_DISK);
        abort_unless($disk->exists($user->avatar_path), 404);

        return response()->file($disk->path($user->avatar_path), [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            // Neue Bilder bekommen über ?v= eine neue Adresse; private Profile nicht in geteilten Caches
            'Cache-Control' => ($user->profile_public ? 'public' : 'private').', max-age=604800',
        ]);
    }

    public function update(Request $request, ImageProcessor $processor): RedirectResponse
    {
        $request->validate([
            // `image` schließt SVG aus; Inhalt wird zusätzlich serverseitig neu kodiert
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:'.self::MAX_KB,
                'dimensions:min_width=100,min_height=100,max_width=8000,max_height=8000'],
        ], [
            'avatar.required' => 'Bitte ein Bild auswählen.',
            'avatar.image' => 'Bitte ein Foto als JPG, PNG oder WebP hochladen.',
            'avatar.mimes' => 'Bitte ein Foto als JPG, PNG oder WebP hochladen.',
            'avatar.max' => 'Das Bild darf höchstens 8 MB groß sein.',
            'avatar.dimensions' => 'Das Bild muss mindestens 100 × 100 Pixel groß sein.',
        ]);

        try {
            $image = $processor->toSquareJpeg($request->file('avatar'), self::SIZE);
        } catch (RuntimeException) {
            throw ValidationException::withMessages(['avatar' => 'Das Bild konnte nicht gelesen werden. Bitte ein anderes Foto versuchen.']);
        }

        $user = $request->user();
        $user->deleteAvatar();
        $path = 'avatars/'.$user->id.'-'.bin2hex(random_bytes(6)).'.jpg';
        Storage::disk(User::AVATAR_DISK)->put($path, $image['data']);
        $user->forceFill(['avatar_path' => $path])->save();

        return back()->with('status', 'Profilbild gespeichert.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->deleteAvatar();

        return back()->with('status', 'Profilbild entfernt.');
    }
}
