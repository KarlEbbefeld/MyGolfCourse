<?php

use App\Modules\Admin\Http\Controllers\CourseController;
use App\Modules\Admin\Http\Controllers\FlyoverController;
use App\Modules\Admin\Http\Controllers\HoleController;
use App\Modules\Admin\Http\Controllers\PanoramaController;
use App\Modules\Admin\Http\Controllers\PlanImageController;
use App\Modules\Admin\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Anmeldung: gemeinsame Seite /anmelden (routes/modules/accounts.php)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:edit-content'])->group(function () {
    Route::get('/', [CourseController::class, 'index'])->name('dashboard');

    Route::get('/meldungen', [ReportController::class, 'index'])->name('reports.index');
    Route::put('/meldungen/{report}', [ReportController::class, 'update'])->whereNumber('report')->name('reports.update');

    Route::get('/plaetze/neu', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/plaetze', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/plaetze/{course:id}', [CourseController::class, 'edit'])->whereNumber('course')->name('courses.edit');
    Route::put('/plaetze/{course:id}', [CourseController::class, 'update'])->whereNumber('course')->name('courses.update');

    Route::get('/bahnen/{hole}', [HoleController::class, 'edit'])->whereNumber('hole')->name('holes.edit');
    Route::put('/bahnen/{hole}', [HoleController::class, 'update'])->whereNumber('hole')->name('holes.update');

    Route::post('/bahnen/{hole}/panoramen', [PanoramaController::class, 'store'])->whereNumber('hole')->name('panoramas.store');
    Route::post('/panoramen/{panorama}/kalibrieren', [PanoramaController::class, 'calibrate'])->whereNumber('panorama')->name('panoramas.calibrate');
    Route::delete('/panoramen/{panorama}', [PanoramaController::class, 'destroy'])->whereNumber('panorama')->name('panoramas.destroy');

    Route::post('/bahnen/{hole}/ueberflug', [FlyoverController::class, 'store'])->whereNumber('hole')->name('flyovers.store');
    Route::delete('/bahnen/{hole}/ueberflug', [FlyoverController::class, 'destroy'])->whereNumber('hole')->name('flyovers.destroy');

    Route::post('/bahnen/{hole}/bahngrafik', [PlanImageController::class, 'store'])->whereNumber('hole')->name('plan.store');
    Route::delete('/bahnen/{hole}/bahngrafik', [PlanImageController::class, 'destroy'])->whereNumber('hole')->name('plan.destroy');
});
