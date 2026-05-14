<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/users/{user}', [\App\Http\Controllers\Api\UserProfileController::class, 'show']);
    Route::get('/users/{user}/posts', [\App\Http\Controllers\Api\UserProfileController::class, 'userPosts']);
    Route::post('/profile/posts', [\App\Http\Controllers\Api\UserProfileController::class, 'createPost']);
    Route::patch('/profile/posts/{thread}', [\App\Http\Controllers\Api\UserProfileController::class, 'updatePost']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Bookings
    Route::apiResource('bookings', \App\Http\Controllers\Api\BookingController::class);
    Route::patch('bookings/{booking}/status', [\App\Http\Controllers\Api\BookingController::class, 'updateStatus']);
    Route::get('bookings/{booking}/invoice', [\App\Http\Controllers\Api\BookingController::class, 'generateInvoice']);
    Route::get('bookings/{booking}/receipt', [\App\Http\Controllers\Api\BookingController::class, 'generateReceipt']);
    // Booking Reports
    Route::get('reports/booking', [\App\Http\Controllers\Api\BookingReportController::class, 'index']);
    Route::get('reports/service', [\App\Http\Controllers\Api\ServiceReportController::class, 'index']);
    Route::get('reports/suburb', [\App\Http\Controllers\Api\SuburbReportController::class, 'index']);
    Route::get('reports/customer', [\App\Http\Controllers\Api\CustomerReportController::class, 'index']);
    Route::get('reports/customer/unbooked', [\App\Http\Controllers\Api\UnbookedCustomerReportController::class, 'index']);

    // Recurring Bookings
    Route::apiResource('booking-recurrings', \App\Http\Controllers\Api\BookingRecurringController::class);
    Route::patch('booking-recurrings/{bookingRecurring}/cancel', [\App\Http\Controllers\Api\BookingRecurringController::class, 'cancel']);

    // Customers
    Route::get('customers/{customer}/pets', [\App\Http\Controllers\Api\PetController::class, 'getByCustomer']);
    Route::get('customers/{customer}/audits', [\App\Http\Controllers\Api\CustomerController::class, 'getHistory']);
    Route::post('customers/{customer}/restore', [\App\Http\Controllers\Api\CustomerController::class, 'restore']);
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);

    // Leads
    Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);
    Route::post('leads/{lead}/convert', [\App\Http\Controllers\Api\LeadController::class, 'convert']);

    // Pets
    Route::apiResource('pets', \App\Http\Controllers\Api\PetController::class);
    Route::get('pets/{pet}/audits', [\App\Http\Controllers\Api\PetController::class, 'getHistory']);
    Route::get('pets/{pet}/waivers', [\App\Http\Controllers\Api\IntakeFormController::class, 'getByPet']);
    Route::get('pets/{pet}/waivers/{type}/history', [\App\Http\Controllers\Api\IntakeFormController::class, 'getHistory']);
    Route::get('waivers/{waiver}', [\App\Http\Controllers\Api\IntakeFormController::class, 'show']);
    Route::post('intake-forms', [\App\Http\Controllers\Api\IntakeFormController::class, 'store']);

    // Blockouts
    Route::apiResource('blockouts', \App\Http\Controllers\Api\BlockoutController::class);

    // Calendar Events
    Route::apiResource('calendar-events', \App\Http\Controllers\Api\CalendarEventController::class);
    Route::get('calendar-events/month', [\App\Http\Controllers\Api\CalendarEventController::class, 'getByMonth']);
    Route::post('calendar-events/sync', [\App\Http\Controllers\Api\CalendarEventController::class, 'syncEvents']);

    // Services
    Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);

    // News
    Route::apiResource('news', \App\Http\Controllers\Api\NewsController::class);
    Route::patch('news/{news}/publish', [\App\Http\Controllers\Api\NewsController::class, 'publish']);

    // Dashboard
    Route::get('dashboard/metrics', [\App\Http\Controllers\Api\DashboardController::class, 'metrics']);
    Route::get('dashboard/activities', [\App\Http\Controllers\Api\DashboardController::class, 'activities']);
    Route::get('dashboard/news', [\App\Http\Controllers\Api\DashboardController::class, 'news']);
    Route::get('dashboard/booking-schedule', [\App\Http\Controllers\Api\DashboardController::class, 'bookingSchedule']);
    Route::get('dashboard/forecast', [\App\Http\Controllers\Api\DashboardController::class, 'forecast']);

    // Forum
    Route::get('forum/threads', [\App\Http\Controllers\Api\ForumController::class, 'index']);
    Route::post('forum/threads', [\App\Http\Controllers\Api\ForumController::class, 'store']);
    Route::get('forum/threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'show']);
    Route::post('forum/threads/{forumThread}/comments', [\App\Http\Controllers\Api\ForumController::class, 'addComment']);
    Route::post('forum/threads/{forumThread}/like', [\App\Http\Controllers\Api\ForumController::class, 'like']);
    Route::get('forum/notifications', [\App\Http\Controllers\Api\ForumController::class, 'notifications']);
    Route::post('forum/notifications/read-all', [\App\Http\Controllers\Api\ForumController::class, 'markAllNotificationsAsRead']);
    Route::post('forum/notifications/{forumNotification}/read', [\App\Http\Controllers\Api\ForumController::class, 'markNotificationAsRead']);
    Route::delete('forum/threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'destroy']);

    // Forum Comments
    Route::post('forum/comments/{forumComment}/like', [\App\Http\Controllers\Api\ForumController::class, 'likeComment']);
    Route::post('forum/comments/{forumComment}/reply', [\App\Http\Controllers\Api\ForumController::class, 'replyToComment']);

    // Forum Groups
    Route::get('forum/groups', [\App\Http\Controllers\Api\ForumGroupController::class, 'index']);
    Route::post('forum/groups', [\App\Http\Controllers\Api\ForumGroupController::class, 'store']);
    Route::get('forum/groups/{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'show']);
    Route::put('forum/groups/{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'update']);
    Route::delete('forum/groups/{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'destroy']);
    Route::post('forum/groups/{forumGroup}/join', [\App\Http\Controllers\Api\ForumGroupController::class, 'join']);
    Route::post('forum/groups/{forumGroup}/leave', [\App\Http\Controllers\Api\ForumGroupController::class, 'leave']);
    Route::get('forum/groups/{forumGroup}/members', [\App\Http\Controllers\Api\ForumGroupController::class, 'members']);

    // Stock Take
    Route::get('stock-take/last/{categoryId}', [\App\Http\Controllers\Api\StockTakeController::class, 'getLast']);
    Route::get('stock-take/history/{categoryId}', [\App\Http\Controllers\Api\StockTakeController::class, 'getHistory']);
    Route::post('stock-take', [\App\Http\Controllers\Api\StockTakeController::class, 'store']);

    // Communication History
    Route::get('communication/sms-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsIndex']);
    Route::post('communication/sms-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsStore']);
    Route::get('communication/sms-history/{smsHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsShow']);
    Route::delete('communication/sms-history/{smsHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsDestroy']);

    Route::get('communication/email-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailIndex']);
    Route::post('communication/email-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailStore']);
    Route::get('communication/email-history/{emailHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailShow']);
    Route::delete('communication/email-history/{emailHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailDestroy']);

    // Income Categories
    Route::apiResource('income-categories', \App\Http\Controllers\Api\IncomeCategoryController::class);

    // Expense Categories
    Route::apiResource('expense-categories', \App\Http\Controllers\Api\ExpenseCategoryController::class);

    // Incomes
    Route::apiResource('incomes', \App\Http\Controllers\Api\IncomeController::class);

    // Recurring Incomes
    Route::apiResource('recurring-incomes', \App\Http\Controllers\Api\RecurringIncomeController::class);

    // Expenses
    Route::apiResource('expenses', \App\Http\Controllers\Api\ExpenseController::class);

    // Recurring Expenses
    Route::apiResource('recurring-expenses', \App\Http\Controllers\Api\RecurringExpenseController::class);

    // Documents
    Route::apiResource('documents', \App\Http\Controllers\Api\DocumentController::class);

    // Reports
    Route::get('reports/benchmarking', [\App\Http\Controllers\Api\BenchmarkingController::class, 'index']);
    Route::get('reports/income', [\App\Http\Controllers\Api\IncomeReportController::class, 'index']);
});
