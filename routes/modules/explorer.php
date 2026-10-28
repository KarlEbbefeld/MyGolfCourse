<?php

use App\Modules\Explorer\Http\Controllers\HoleShowController;
use Illuminate\Support\Facades\Route;

Route::get('/plaetze/{course:slug}/bahn/{number}', HoleShowController::class)
    ->whereNumber('number')
    ->name('holes.show');
