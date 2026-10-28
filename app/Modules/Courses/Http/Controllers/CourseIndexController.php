<?php

namespace App\Modules\Courses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platzübersicht mit Suche (Name, Ort, Club) und Filter nach Region und Lochzahl.
 */
class CourseIndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'loecher' => ['nullable', 'integer', 'in:9,18,27'],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));

        $courses = Course::query()
            ->published()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('club_name', 'like', $like)
                    ->orWhere('postal_code', 'like', $like));
            })
            ->when($filters['region'] ?? null, fn ($query, $region) => $query->where('region', $region))
            ->when($filters['loecher'] ?? null, fn ($query, $holes) => $query->where('holes_count', $holes))
            ->with('holes.tees')
            ->orderBy('is_demo')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $regions = Course::query()->published()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region');

        return view('courses.index', [
            'courses' => $courses,
            'regions' => $regions,
            'filters' => $filters,
        ]);
    }
}
