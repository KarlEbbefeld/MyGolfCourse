<?php

use App\Modules\Community\Http\Controllers\AvatarController;
use App\Modules\Community\Http\Controllers\ChatController;
use App\Modules\Community\Http\Controllers\ClubController;
use App\Modules\Community\Http\Controllers\CommunityController;
use App\Modules\Community\Http\Controllers\NotificationController;
use App\Modules\Community\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

// Öffentlich (Inhalte je nach Privatsphäre des Profils)
Route::get('/spieler/{user:username}', [PlayerController::class, 'show'])->name('players.show');
Route::get('/plaetze/{course:slug}/club', [ClubController::class, 'show'])->name('clubs.show');
Route::get('/spieler/{user:username}/bild', [AvatarController::class, 'show'])->middleware('throttle:300,1')->name('players.avatar');

Route::middleware('auth')->group(function () {
    Route::get('/community', [CommunityController::class, 'index'])->name('community.index');

    Route::post('/konto/profilbild', [AvatarController::class, 'update'])->middleware('throttle:10,1')->name('avatar.update');
    Route::delete('/konto/profilbild', [AvatarController::class, 'destroy'])->name('avatar.destroy');

    Route::post('/spieler/{user:username}/folgen', [PlayerController::class, 'follow'])->middleware('throttle:30,1')->name('players.follow');
    Route::post('/spieler/{user:username}/blockieren', [PlayerController::class, 'block'])->middleware('throttle:20,1')->name('players.block');
    Route::post('/spieler/{user:username}/nachricht', [PlayerController::class, 'message'])->middleware('throttle:20,1')->name('players.message');
    Route::post('/spieler/{user:username}/melden', [PlayerController::class, 'report'])->middleware('throttle:10,60')->name('players.report');

    Route::post('/plaetze/{course:slug}/club', [ClubController::class, 'toggle'])->middleware('throttle:10,1')->name('clubs.toggle');
    Route::get('/plaetze/{course:slug}/club/chat', [ChatController::class, 'club'])->name('chats.club');

    Route::get('/nachrichten', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/nachrichten/{conversation}', [ChatController::class, 'show'])->whereNumber('conversation')->name('chats.show');
    Route::get('/nachrichten/{conversation}/neu', [ChatController::class, 'poll'])->whereNumber('conversation')->middleware('throttle:120,1')->name('chats.poll');
    Route::post('/nachrichten/{conversation}', [ChatController::class, 'store'])->whereNumber('conversation')->middleware('throttle:20,1')->name('chats.store');
    Route::post('/nachrichten/{conversation}/{message}/melden', [ChatController::class, 'report'])
        ->whereNumber(['conversation', 'message'])->middleware('throttle:10,60')->name('chats.report');

    Route::get('/mitteilungen', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/mitteilungen/zaehler', [NotificationController::class, 'counts'])->middleware('throttle:120,1')->name('notifications.counts');
});
