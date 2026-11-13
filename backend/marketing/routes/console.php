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

// connected stores (Shopify, WooCommerce, WordPress, Magento): each connection syncs at its own pace; this only starts the ones that are due
Illuminate\Support\Facades\Schedule::command('integrations:sync')->everyFifteenMinutes()->withoutOverlapping();
