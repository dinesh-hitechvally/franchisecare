<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Cron endpoints for external schedulers (e.g. cPanel) - no auth, direct access
Route::get('cron/xero/push-pending-bookings', [\App\Http\Controllers\Cron\XeroController::class, 'pushPendingBookings']);
Route::get('cron/xero/push-pending-customers', [\App\Http\Controllers\Cron\XeroController::class, 'pushPendingCustomers']);
Route::get('cron/xero/push-pending-purchases', [\App\Http\Controllers\Cron\XeroController::class, 'pushPendingPurchases']);
Route::get('cron/xero/push-pending-inventory-items', [\App\Http\Controllers\Cron\XeroController::class, 'pushPendingInventoryItems']);
Route::get('cron/xero/push-pending-services', [\App\Http\Controllers\Cron\XeroController::class, 'pushPendingServices']);
