<?php

namespace App\Modules\Favorites\Support;

use App\Modules\Courses\Models\Course;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Zuletzt angesehene Plätze: angemeldet in der Datenbank (max. 20), als Gast nur in der Sitzung (max. 10).
 */
class RecentlyViewed
{
    private const MAX_USER = 20;

    private const MAX_GUEST = 10;

    private const SESSION_KEY = 'recently_viewed';

    public static function record(Course $course): void
    {
        // Nebensache: darf das Anzeigen einer Seite nie verhindern (z. B. bei kurz gesperrter Datenbank)
        try {
            self::store($course);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private static function store(Course $course): void
    {
        $user = auth()->user();

        if (! $user) {
            $ids = array_values(array_diff(session(self::SESSION_KEY, []), [$course->id]));
            array_unshift($ids, $course->id);
            session()->put(self::SESSION_KEY, array_slice($ids, 0, self::MAX_GUEST));

            return;
        }

        DB::table('course_views')->upsert(
            [['user_id' => $user->id, 'course_id' => $course->id, 'viewed_at' => now()]],
            ['user_id', 'course_id'],
            ['viewed_at'],
        );

        $keep = DB::table('course_views')->where('user_id', $user->id)->orderByDesc('viewed_at')->limit(self::MAX_USER)->pluck('course_id');
        DB::table('course_views')->where('user_id', $user->id)->whereNotIn('course_id', $keep)->delete();
    }

    /** @return Collection<int, Course> */
    public static function courses(int $limit = 6): Collection
    {
        $user = auth()->user();

        if ($user) {
            return $user->viewedCourses()->published()->with('holes.tees')->limit($limit)->get();
        }

        $ids = array_slice(session(self::SESSION_KEY, []), 0, $limit);
        if ($ids === []) {
            return collect();
        }

        $courses = Course::query()->published()->with('holes.tees')->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $courses->get($id))->filter()->values();
    }
}
