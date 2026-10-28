<?php

use App\Modules\Courses\Http\Controllers\CourseIndexController;
use App\Modules\Courses\Http\Controllers\CourseShowController;
use Illuminate\Support\Facades\Route;

Route::get('/plaetze', CourseIndexController::class)->name('courses.index');
Route::get('/plaetze/{course:slug}', CourseShowController::class)->name('courses.show');
