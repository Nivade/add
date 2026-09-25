<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Preparation is a minutes-scale thing, so the check is cheap and frequent.
Schedule::command('reminders:dispatch')->everyMinute()->withoutOverlapping();
Schedule::command('future-reminders:dispatch')->everyMinute()->withoutOverlapping();
Schedule::command('calendar:sync')->hourly()->withoutOverlapping();
