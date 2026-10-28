<?php

namespace App\Modules\Player\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use App\Modules\Explorer\Http\Controllers\HoleShowController;
use App\Modules\Explorer\Support\HoleExplorer;
use App\Modules\Player\Models\HoleNote;
use App\Modules\Player\Support\Bag;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Persönliches Yardage Book eines Platzes zum Ausdrucken: alle Bahnen mit Distanzen, Hindernissen,
 * eigenem Spielplan (aus „Mein Bag“) und eigenen Notizen.
 */
class YardageBookController extends Controller
{
    public function __invoke(Request $request, Course $course): View
    {
        abort_unless($course->isPublished(), 404);

        $course->load(['teeSets', 'holes.tees']);
        $teeSet = HoleShowController::selectedTeeSet($course, $request->query('abschlag'));
        $user = $request->user();

        $notes = HoleNote::query()
            ->where('user_id', $user->id)
            ->whereIn('hole_id', $course->holes->pluck('id'))
            ->pluck('body', 'hole_id');

        return view('player.yardage-book', [
            'course' => $course,
            'teeSet' => $teeSet,
            'bag' => Bag::for($user),
            'notes' => $notes,
            'holes' => $course->holes->map(fn ($hole) => [
                'hole' => $hole,
                'tee' => $hole->teeFor($teeSet),
                'explorer' => new HoleExplorer($hole, $teeSet),
            ]),
        ]);
    }
}
