<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Close trips with no new positions for >5 min — runs every minute via cron.
Schedule::command('gpsmanager:close-stale-trips')->everyMinute()->withoutOverlapping();

// Evaluate stateful alarms (parking_long, device_offline) — every 5 min.
Schedule::command('gpsmanager:evaluate-alarms')->everyFiveMinutes()->withoutOverlapping();
