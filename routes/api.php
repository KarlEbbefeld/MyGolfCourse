<?php

use App\Modules\Explorer\Http\Controllers\HoleExplorerApiController;
use Illuminate\Support\Facades\Route;

// Präfix /api/v1 (bootstrap/app.php)
Route::get('/holes/{hole}/explorer', HoleExplorerApiController::class)
    ->whereNumber('hole')
    ->middleware('throttle:120,1')
    ->name('api.holes.explorer');
