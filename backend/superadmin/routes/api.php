<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FranchiseController;
use App\Http\Controllers\Api\FranchiseUserController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\AdminUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication & Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/profile', [AdminUserController::class, 'updateProfile']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('metrics', [DashboardController::class, 'metrics']);
        Route::get('revenue-chart', [DashboardController::class, 'revenueChart']);
        Route::get('franchises-by-status', [DashboardController::class, 'franchisesByStatus']);
        Route::get('recent-activities', [DashboardController::class, 'recentActivities']);
        Route::get('top-franchises', [DashboardController::class, 'topFranchises']);
    });

    /*
    |--------------------------------------------------------------------------
    | Franchises
    |--------------------------------------------------------------------------
    */
    Route::apiResource('franchises', FranchiseController::class);
    Route::get('franchises/{franchise}/history', [FranchiseController::class, 'getHistory']);
    Route::patch('franchises/{franchise}/status', [FranchiseController::class, 'updateStatus']);
    Route::get('franchises/{franchise}/users', [FranchiseUserController::class, 'getByFranchise']);

    /*
    |--------------------------------------------------------------------------
    | Franchise Users
    |--------------------------------------------------------------------------
    */
    Route::apiResource('franchise-users', FranchiseUserController::class);
    Route::post('franchise-users/{franchise_user}/reset-password', [FranchiseUserController::class, 'resetPassword']);

    /*
    |--------------------------------------------------------------------------
    | Admin Users
    |--------------------------------------------------------------------------
    */
    Route::apiResource('admin-users', AdminUserController::class);

    /*
    |--------------------------------------------------------------------------
    | News
    |--------------------------------------------------------------------------
    */
    Route::apiResource('news', NewsController::class);
    Route::post('news/{news}/publish', [NewsController::class, 'publish']);

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    */
    Route::apiResource('services', ServiceController::class);
    Route::post('services/reorder', [ServiceController::class, 'reorder']);

    /*
    |--------------------------------------------------------------------------
    | Support Tickets
    |--------------------------------------------------------------------------
    */
    Route::get('support-tickets/stats', [SupportTicketController::class, 'stats']);
    Route::apiResource('support-tickets', SupportTicketController::class)->only(['index', 'show', 'update']);
    Route::post('support-tickets/{support_ticket}/reply', [SupportTicketController::class, 'reply']);
    Route::post('support-tickets/{support_ticket}/assign', [SupportTicketController::class, 'assign']);
    Route::post('support-tickets/{support_ticket}/resolve', [SupportTicketController::class, 'resolve']);

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('franchise-performance', [ReportController::class, 'franchisePerformance']);
        Route::get('revenue', [ReportController::class, 'revenueReport']);
        Route::get('franchise-growth', [ReportController::class, 'franchiseGrowth']);
        Route::get('payment-status', [ReportController::class, 'paymentStatus']);
        Route::get('export/franchises', [ReportController::class, 'exportFranchises']);
    });

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::get('settings', [SettingsController::class, 'index']);
    Route::get('settings/groups', [SettingsController::class, 'groups']);
    Route::get('settings/group/{group}', [SettingsController::class, 'getByGroup']);
    Route::get('settings/{key}', [SettingsController::class, 'show']);
    Route::put('settings/{key}', [SettingsController::class, 'update']);
    Route::post('settings/bulk', [SettingsController::class, 'updateBulk']);
});
