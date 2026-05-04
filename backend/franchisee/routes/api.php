<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Bookings
    Route::apiResource('bookings', \App\Http\Controllers\Api\BookingController::class);
    Route::patch('bookings/{booking}/status', [\App\Http\Controllers\Api\BookingController::class, 'updateStatus']);
    Route::get('bookings/{booking}/invoice', [\App\Http\Controllers\Api\BookingController::class, 'generateInvoice']);
    Route::get('bookings/{booking}/receipt', [\App\Http\Controllers\Api\BookingController::class, 'generateReceipt']);

    // Recurring Bookings
    Route::apiResource('booking-recurrings', \App\Http\Controllers\Api\BookingRecurringController::class);
    Route::patch('booking-recurrings/{bookingRecurring}/cancel', [\App\Http\Controllers\Api\BookingRecurringController::class, 'cancel']);

    // Customers
    Route::get('customers/{customer}/pets', [\App\Http\Controllers\Api\PetController::class, 'getByCustomer']);
    Route::get('customers/{customer}/audits', [\App\Http\Controllers\Api\CustomerController::class, 'getHistory']);
    Route::post('customers/{customer}/restore', [\App\Http\Controllers\Api\CustomerController::class, 'restore']);
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);

    // Pets
    Route::apiResource('pets', \App\Http\Controllers\Api\PetController::class);
    Route::get('pets/{pet}/audits', [\App\Http\Controllers\Api\PetController::class, 'getHistory']);
    Route::get('pets/{pet}/waivers', [\App\Http\Controllers\Api\IntakeFormController::class, 'getByPet']);
    Route::get('pets/{pet}/waivers/{type}/history', [\App\Http\Controllers\Api\IntakeFormController::class, 'getHistory']);
    Route::get('waivers/{waiver}', [\App\Http\Controllers\Api\IntakeFormController::class, 'show']);
    Route::post('intake-forms', [\App\Http\Controllers\Api\IntakeFormController::class, 'store']);

    // Blockouts
    Route::apiResource('blockouts', \App\Http\Controllers\Api\BlockoutController::class);

    // Services
    Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);

    // News
    Route::apiResource('news', \App\Http\Controllers\Api\NewsController::class);
    Route::patch('news/{news}/publish', [\App\Http\Controllers\Api\NewsController::class, 'publish']);

    // Forum
    Route::get('forum/threads', [\App\Http\Controllers\Api\ForumController::class, 'index']);
    Route::post('forum/threads', [\App\Http\Controllers\Api\ForumController::class, 'store']);
    Route::get('forum/threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'show']);
    Route::post('forum/threads/{forumThread}/comments', [\App\Http\Controllers\Api\ForumController::class, 'addComment']);
    Route::post('forum/threads/{forumThread}/like', [\App\Http\Controllers\Api\ForumController::class, 'like']);
    Route::delete('forum/threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'destroy']);
});
