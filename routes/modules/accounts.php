<?php

use App\Modules\Accounts\Http\Controllers\AccountController;
use App\Modules\Accounts\Http\Controllers\EmailVerificationController;
use App\Modules\Accounts\Http\Controllers\PasswordResetController;
use App\Modules\Accounts\Http\Controllers\RegistrationController;
use App\Modules\Accounts\Http\Controllers\SessionController;
use App\Modules\Favorites\Http\Controllers\FavoriteController;
use Illuminate\Support\Facades\Route;

// Eine Anmeldung für alle (Name „login“: dorthin leitet Laravel bei geschützten Seiten um)
Route::middleware('guest')->group(function () {
    Route::get('/anmelden', [SessionController::class, 'create'])->name('login');
    Route::post('/anmelden', [SessionController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
    Route::get('/registrieren', [RegistrationController::class, 'create'])->name('register');
    Route::post('/registrieren', [RegistrationController::class, 'store'])->middleware('throttle:10,60')->name('register.store');

    Route::get('/passwort-vergessen', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/passwort-vergessen', [PasswordResetController::class, 'email'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/passwort-zuruecksetzen/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/passwort-zuruecksetzen', [PasswordResetController::class, 'update'])->middleware('throttle:10,10')->name('password.update');

    Route::get('/merkliste/anmelden/{course:slug}', [FavoriteController::class, 'login'])->name('favorites.login');
});

Route::middleware('auth')->group(function () {
    Route::post('/abmelden', [SessionController::class, 'destroy'])->name('logout');

    // E-Mail-Bestätigung (Namen von Laravel vorgegeben)
    Route::get('/email/bestaetigen', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/bestaetigen/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/bestaetigungslink', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:3,10')->name('verification.send');

    Route::get('/konto', [AccountController::class, 'show'])->name('account.show');
    Route::get('/konto/einstellungen', [AccountController::class, 'settings'])->name('account.settings');
    Route::put('/konto/einstellungen', [AccountController::class, 'updateSettings'])->name('account.settings.update');
    Route::put('/konto/passwort', [AccountController::class, 'updatePassword'])->middleware('throttle:10,10')->name('account.password');
    Route::get('/konto/meine-daten', [AccountController::class, 'export'])->middleware('throttle:5,1')->name('account.export');
    Route::delete('/konto', [AccountController::class, 'destroy'])->middleware('throttle:5,10')->name('account.destroy');

    Route::post('/merkliste/{course:slug}', [FavoriteController::class, 'toggle'])->middleware('throttle:60,1')->name('favorites.toggle');
});
