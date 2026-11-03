<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Illuminate\Support\Facades\Schedule::command('campaigns:dispatch-scheduled')->everyMinute()->withoutOverlapping();

Illuminate\Support\Facades\Schedule::command('automation:process')->everyMinute()->withoutOverlapping();

// "a date arrives" and "has not opened emails" workflows: checked once a day (server time, UTC)
Illuminate\Support\Facades\Schedule::command('automation:time-triggers')->dailyAt('00:15')->withoutOverlapping();
