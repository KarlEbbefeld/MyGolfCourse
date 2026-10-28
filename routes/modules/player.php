<?php

use App\Modules\Player\Http\Controllers\NoteController;
use App\Modules\Player\Http\Controllers\ProfileController;
use App\Modules\Player\Http\Controllers\RoundController;
use App\Modules\Player\Http\Controllers\YardageBookController;
use Illuminate\Support\Facades\Route;

// Vorteile mit Konto (Modul Player)
Route::middleware('auth')->group(function () {
    Route::put('/bahnen/{hole}/notiz', [NoteController::class, 'update'])->whereNumber('hole')->middleware('throttle:60,1')->name('notes.update');

    Route::post('/plaetze/{course:slug}/runden', [RoundController::class, 'store'])->middleware('throttle:20,1')->name('rounds.store');
    Route::delete('/runden/{round}', [RoundController::class, 'destroy'])->whereNumber('round')->name('rounds.destroy');

    Route::put('/konto/bag', [ProfileController::class, 'bag'])->name('bag.update');

    Route::get('/plaetze/{course:slug}/yardage-book', YardageBookController::class)->name('yardage-book');
});
