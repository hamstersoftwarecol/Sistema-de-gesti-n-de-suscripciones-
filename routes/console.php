<?php

use App\Models\Setting;
use App\Support\Installer;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Add a single cron entry on the server:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
| or call the web cron URL shown in Settings → Cron from an external service.
|
*/

Schedule::command('billing:run')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->when(fn () => Installer::isInstalled());

Schedule::command('reminders:send')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->when(fn () => Installer::isInstalled() && Setting::bool('reminders_enabled', true));

Schedule::command('backup:create --label=auto --prune')
    ->dailyAt('02:00')
    ->when(fn () => Installer::isInstalled() && Setting::bool('auto_backup', false));
