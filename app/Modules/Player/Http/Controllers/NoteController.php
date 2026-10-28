<?php

namespace App\Modules\Player\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Hole;
use App\Modules\Player\Models\HoleNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Private Notiz zu einer Bahn speichern (leer = löschen). Die Bahnseite speichert automatisch per fetch.
 */
class NoteController extends Controller
{
    public function update(Request $request, Hole $hole): JsonResponse|RedirectResponse
    {
        $hole->loadMissing('course');
        abort_unless($hole->course->isPublished(), 404);

        $data = $request->validate(['body' => ['nullable', 'string', 'max:2000']]);
        $body = trim((string) ($data['body'] ?? ''));
        $user = $request->user();

        if ($body === '') {
            HoleNote::query()->where('user_id', $user->id)->where('hole_id', $hole->id)->delete();
        } else {
            HoleNote::query()->updateOrCreate(['user_id' => $user->id, 'hole_id' => $hole->id], ['body' => $body]);
        }

        if ($request->expectsJson()) {
            return response()->json(['saved' => true, 'at' => now()->format('H:i')]);
        }

        return back()->with('status', 'Notiz gespeichert.');
    }
}
