<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Jedes Modul hat eine eigene Routen-Datei (docs/ARCHITECTURE.md § 5) – weniger Merge-Konflikte
require __DIR__.'/modules/courses.php';
require __DIR__.'/modules/explorer.php';
require __DIR__.'/modules/accounts.php';
require __DIR__.'/modules/player.php';
require __DIR__.'/modules/community.php';
require __DIR__.'/modules/admin.php';

// Rechtliches (Angaben aus config/legal.php bzw. der Server-.env)
Route::view('/impressum', 'pages.imprint')->name('imprint');
Route::view('/datenschutz', 'pages.privacy')->name('privacy');
Route::view('/so-funktionierts', 'pages.about')->name('about');
