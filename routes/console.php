<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check every hour for users whose Drive backup schedule is due.
// (Requires the system cron: * * * * * php artisan schedule:run)
Schedule::command('backups:run')->hourly();
