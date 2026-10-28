<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Hole;
use App\Modules\Explorer\Models\Flyover;
use App\Modules\Media\MediaStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Überflug-Video einer Bahn hochladen oder entfernen. Pro Bahn ein Video; ein neues ersetzt das alte.
 */
class FlyoverController extends Controller
{
    public function store(Request $request, Hole $hole): RedirectResponse
    {
        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime', 'max:512000'],
            'poster' => ['nullable', 'image', 'max:10240'],
        ], [
            'video.required' => 'Bitte ein Video auswählen. Falls die Datei zu groß ist, kommt sie gar nicht an – Grenze siehe unten.',
            'video.mimetypes' => 'Bitte ein MP4-Video (H.264) hochladen.',
        ]);

        $directory = MediaStore::holeDirectory($hole);
        $video = MediaStore::store($request->file('video'), $directory, 'ueberflug');
        $poster = $request->hasFile('poster') ? MediaStore::store($request->file('poster'), $directory, 'ueberflug-vorschau') : null;

        $this->removeExisting($hole);

        Flyover::create([
            'hole_id' => $hole->id,
            'video_url' => $video,
            'poster_url' => $poster,
            'direction' => 'tee_to_green',
        ]);

        return redirect()->route('admin.holes.edit', $hole)->with('status', 'Überflug-Video gespeichert.');
    }

    public function destroy(Hole $hole): RedirectResponse
    {
        $this->removeExisting($hole);

        return redirect()->route('admin.holes.edit', $hole)->with('status', 'Überflug-Video entfernt.');
    }

    private function removeExisting(Hole $hole): void
    {
        Flyover::query()->where('hole_id', $hole->id)->get()->each(function (Flyover $flyover) {
            MediaStore::delete($flyover->video_url);
            MediaStore::delete($flyover->poster_url);
            $flyover->delete();
        });
    }
}
