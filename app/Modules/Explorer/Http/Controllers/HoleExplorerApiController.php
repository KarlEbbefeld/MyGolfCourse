<?php

namespace App\Modules\Explorer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Courses\Models\Hole;
use App\Modules\Explorer\Support\HoleExplorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Viewer-Daten einer Bahn (GET /api/v1/holes/{hole}/explorer?abschlag=yellow).
 * Dieselbe Schnittstelle kann später eine native App nutzen (docs/ARCHITECTURE.md § 6).
 */
class HoleExplorerApiController extends Controller
{
    public function __invoke(Request $request, Hole $hole): JsonResponse
    {
        $hole->load(['course.teeSets', 'tees']);
        abort_unless($hole->course->isPublished(), 404);

        $teeSet = HoleShowController::selectedTeeSet($hole->course, $request->query('abschlag'));

        return response()
            ->json((new HoleExplorer($hole, $teeSet))->toViewerArray())
            ->header('Cache-Control', 'public, max-age=300');
    }
}
