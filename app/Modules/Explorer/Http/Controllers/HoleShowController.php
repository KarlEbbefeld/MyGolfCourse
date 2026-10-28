<?php

namespace App\Modules\Explorer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use App\Modules\Courses\Models\Hole;
use App\Modules\Courses\Models\TeeSet;
use App\Modules\Explorer\Support\HoleExplorer;
use App\Modules\Favorites\Support\RecentlyViewed;
use App\Modules\Player\Models\HoleNote;
use App\Modules\Player\Support\Bag;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bahnseite: Street-View-Erkundung, Fakten, Distanzband, Hindernisse, Scorecard-Leiste.
 */
class HoleShowController extends Controller
{
    public function __invoke(Request $request, Course $course, int $number): View
    {
        abort_unless($course->isPublished(), 404);

        $course->load(['teeSets', 'holes.tees']);

        /** @var Hole $hole */
        $hole = $course->holes->firstWhere('number', $number) ?? abort(404);
        $teeSet = self::selectedTeeSet($course, $request->query('abschlag'));

        $explorer = new HoleExplorer($hole, $teeSet);
        RecentlyViewed::record($course);
        $user = $request->user();
        $tee = $hole->teeFor($teeSet);
        $bag = Bag::for($user);

        return view('explorer.hole', [
            'bag' => $bag,
            'plan' => $tee ? $bag->plan($tee->length_m, $hole->par) : [],
            'note' => $user ? HoleNote::query()->where('user_id', $user->id)->where('hole_id', $hole->id)->value('body') : null,
            'course' => $course,
            'hole' => $hole,
            'teeSet' => $teeSet,
            'tee' => $hole->teeFor($teeSet),
            'explorer' => $explorer,
            'previous' => $course->holes->firstWhere('number', $number - 1),
            'next' => $course->holes->firstWhere('number', $number + 1),
        ]);
    }

    /** Gewählte Abschlagfarbe aus ?abschlag=yellow, sonst die Lieblingsfarbe des Nutzers, sonst die erste des Platzes. */
    public static function selectedTeeSet(Course $course, mixed $color): ?TeeSet
    {
        $color = is_string($color) ? $color : auth()->user()?->setting('default_tee_color');

        return $course->teeSets->first(fn (TeeSet $t) => is_string($color) && $t->color->value === $color)
            ?? $course->defaultTeeSet();
    }
}
