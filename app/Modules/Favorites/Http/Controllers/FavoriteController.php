<?php

namespace App\Modules\Favorites\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Merkliste: Platz merken bzw. entfernen. Antwortet mit JSON (Knopf ohne Neuladen) oder leitet zurück.
 */
class FavoriteController extends Controller
{
    public function toggle(Request $request, Course $course): JsonResponse|RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $user = $request->user();
        $changes = $user->favoriteCourses()->toggle($course->id);
        $saved = in_array($course->id, $changes['attached'], true);

        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved]);
        }

        return back()->with('status', $saved ? $course->name.' ist auf deiner Merkliste.' : $course->name.' wurde von der Merkliste entfernt.');
    }

    /** Für Gäste: anmelden und danach zurück zum Platz. */
    public function login(Course $course): RedirectResponse
    {
        session()->put('url.intended', route('courses.show', $course));

        return redirect()->route('login')->with('status', 'Melde dich an, um Plätze auf deiner Merkliste zu speichern.');
    }
}
