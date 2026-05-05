<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ops:heartbeat', function () {
    Log::info('ops:heartbeat executed by scheduler sample.');

    $this->comment('Heartbeat logged.');
})->purpose('Write a scheduler heartbeat log entry');

// Scheduler logs are written through the default logger at storage/logs/laravel.log.
Schedule::command('ops:heartbeat')->everyFiveMinutes()->withoutOverlapping();
