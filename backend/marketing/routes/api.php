<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('t/o/{token}.gif', [TrackingController::class, 'open']);
Route::get('t/c/{token}', [TrackingController::class, 'click']);
Route::get('unsubscribe/{token}', [TrackingController::class, 'unsubscribe']);

// Authenticated (all data is scoped to the user's business)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('dashboard', [LogController::class, 'dashboard']);

    Route::post('contacts/import', [ContactController::class, 'import']);
    Route::apiResource('contacts', ContactController::class)->except('show');
    Route::apiResource('lists', ContactListController::class)->except('show')->parameters(['lists' => 'list']);
    Route::apiResource('templates', TemplateController::class)->except('show');
    Route::apiResource('layouts', LayoutController::class)->only(['index', 'store', 'destroy']);

    Route::post('campaigns/preview', [CampaignController::class, 'preview']);
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send']);
    Route::post('campaigns/{campaign}/test', [CampaignController::class, 'test']);

    Route::get('logs', [LogController::class, 'index']);
    Route::get('logs/{log}', [LogController::class, 'show']);

    Route::get('images/providers', [ImageController::class, 'providers']);
    Route::get('images/search', [ImageController::class, 'search']);

    Route::get('settings', [SettingsController::class, 'show']);
    Route::put('settings', [SettingsController::class, 'update']);
});
