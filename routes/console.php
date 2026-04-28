<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule subscription due notifications to run daily at 9 AM
Schedule::command('subscriptions:notify-due-soon')
    ->dailyAt('09:00')
    ->description('Notify tenants 3 days before subscription due');
