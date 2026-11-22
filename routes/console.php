<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Production Scheduled Tasks — HisabKitab SaaS
|--------------------------------------------------------------------------
*/

// Check expired merchant subscriptions daily at midnight
Schedule::command('hisabkitab:check-subscriptions')->daily();

// Automated daily database backup at 02:00 AM
Schedule::command('hisabkitab:backup --retention=7')->dailyAt('02:00');

// Clean up expired password reset tokens daily
Schedule::command('auth:clear-resets')->daily();

