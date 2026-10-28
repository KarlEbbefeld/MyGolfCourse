<?php

namespace App\Modules\Player\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use App\Modules\Player\Models\Round;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Runden-Tagebuch: gespielte Runde eintragen oder löschen.
 */
class RoundController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished(), 404);

        $data = $request->validate([
            'played_on' => ['required', 'date', 'before_or_equal:today', 'after:2000-01-01'],
            'tee_set_id' => ['nullable', 'integer', Rule::exists('tee_sets', 'id')->where('course_id', $course->id)],
            'holes_played' => ['required', 'integer', 'in:9,18,27'],
            'score' => ['nullable', 'integer', 'between:18,250'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'played_on.before_or_equal' => 'Das Datum darf nicht in der Zukunft liegen.',
        ]);

        $request->user()->rounds()->create($data + ['course_id' => $course->id]);

        return back()->with('status', 'Runde eingetragen. Schönes Spiel!');
    }

    public function destroy(Request $request, Round $round): RedirectResponse
    {
        abort_unless($round->user_id === $request->user()->id, 403);
        $round->delete();

        return back()->with('status', 'Runde gelöscht.');
    }
}
