<?php

namespace App\Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use App\Modules\Favorites\Support\RecentlyViewed;
use Illuminate\View\View;

/**
 * Platzdetailseite: Luftbild, Fakten, Scorecard aller Bahnen.
 */
class CourseShowController extends Controller
{
    public function __invoke(Course $course): View
    {
        abort_unless($course->isPublished(), 404);

        $course->load(['teeSets', 'holes.tees']);
        RecentlyViewed::record($course);

        // Gesamtlänge je Abschlagfarbe für die Faktenzeile und die Scorecard-Summe
        $totals = $course->teeSets->mapWithKeys(fn ($teeSet) => [
            $teeSet->id => $course->holes->sum(fn ($hole) => $hole->teeFor($teeSet)?->length_m ?? 0),
        ]);

        return view('courses.show', [
            'course' => $course,
            'teeSets' => $course->teeSets,
            'holes' => $course->holes,
            'totals' => $totals,
            'parTotal' => $course->par_total ?? $course->holes->sum('par'),
            'jsonLd' => $this->structuredData($course),
            'isFavorite' => (bool) auth()->user()?->favoriteCourses()->whereKey($course->id)->exists(),
            'isHome' => auth()->user()?->homeCourseId() === $course->id,
            'myRounds' => auth()->user()?->rounds()->where('course_id', $course->id)->with('teeSet')->latest('played_on')->limit(5)->get() ?? collect(),
        ]);
    }

    /** schema.org/GolfCourse für Suchmaschinen (hier statt im Template: '@context' kollidiert mit Blade-Direktiven). */
    private function structuredData(Course $course): string
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'GolfCourse',
            'name' => $course->name,
            'description' => $course->short_description,
            'url' => route('courses.show', $course),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'postalCode' => $course->postal_code,
                'addressLocality' => $course->city,
                'addressRegion' => $course->region,
                'addressCountry' => $course->country,
            ]),
            'geo' => $course->lat !== null ? ['@type' => 'GeoCoordinates', 'latitude' => $course->lat, 'longitude' => $course->lng] : null,
        ]);

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }
}
