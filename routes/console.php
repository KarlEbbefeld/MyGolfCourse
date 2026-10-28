<?php

use Illuminate\Support\Facades\Schedule;

// Shared Hosting (Hostinger) hat keine dauerhaften Queue-Worker: Der minütliche Cron
// `php artisan schedule:run` arbeitet die Queue ab und beendet sich danach (wie im Golf Marketplace).
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
