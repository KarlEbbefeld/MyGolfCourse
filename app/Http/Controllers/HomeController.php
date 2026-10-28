<?php

namespace App\Http\Controllers;

use App\Modules\Courses\Models\Course;
use App\Modules\Explorer\Models\Flyover;
use App\Modules\Favorites\Support\RecentlyViewed;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Hintergrund der Startseite: ein echter Überflug eines veröffentlichten Platzes (ohne Ton, in Schleife)
        $heroFlyover = Flyover::query()
            ->whereHas('hole.course', fn ($q) => $q->published())
            ->latest('id')
            ->first();

        return view('pages.home', [
            'courses' => Course::query()->published()->with('holes.tees')->orderBy('is_demo')->orderByDesc('published_at')->limit(6)->get(),
            'courseCount' => Course::query()->published()->count(),
            'heroFlyover' => $heroFlyover,
            'recent' => RecentlyViewed::courses(3),
        ]);
    }
}
