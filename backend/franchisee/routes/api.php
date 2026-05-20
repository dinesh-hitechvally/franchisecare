<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication)
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
    | Authentication & User Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/users/{user}', [\App\Http\Controllers\Api\UserProfileController::class, 'show']);
    Route::get('/users/{user}/posts', [\App\Http\Controllers\Api\UserProfileController::class, 'userPosts']);
    Route::post('/profile/posts', [\App\Http\Controllers\Api\UserProfileController::class, 'createPost']);
    Route::patch('/profile/posts/{thread}', [\App\Http\Controllers\Api\UserProfileController::class, 'updatePost']);
    Route::post('/user/profile', [\App\Http\Controllers\Api\UserProfileController::class, 'updateProfile']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->group(function () {
        Route::get('metrics', [\App\Http\Controllers\Api\DashboardController::class, 'metrics']);
        Route::get('activities', [\App\Http\Controllers\Api\DashboardController::class, 'activities']);
        Route::get('news', [\App\Http\Controllers\Api\DashboardController::class, 'news']);
        Route::get('booking-schedule', [\App\Http\Controllers\Api\DashboardController::class, 'bookingSchedule']);
        Route::get('forecast', [\App\Http\Controllers\Api\DashboardController::class, 'forecast']);
    });

    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */
    Route::apiResource('leads', \App\Http\Controllers\Api\LeadController::class);
    Route::post('leads/{lead}/convert', [\App\Http\Controllers\Api\LeadController::class, 'convert']);

    /*
    |--------------------------------------------------------------------------
    | Customers
    |--------------------------------------------------------------------------
    */
    Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
    Route::get('customers/{customer}/pets', [\App\Http\Controllers\Api\PetController::class, 'getByCustomer']);
    Route::get('customers/{customer}/audits', [\App\Http\Controllers\Api\CustomerController::class, 'getHistory']);
    Route::post('customers/{customer}/restore', [\App\Http\Controllers\Api\CustomerController::class, 'restore']);

    /*
    |--------------------------------------------------------------------------
    | Pets & Waivers
    |--------------------------------------------------------------------------
    */
    Route::apiResource('pets', \App\Http\Controllers\Api\PetController::class);
    Route::get('pets/{pet}/audits', [\App\Http\Controllers\Api\PetController::class, 'getHistory']);
    Route::get('pets/{pet}/waivers', [\App\Http\Controllers\Api\IntakeFormController::class, 'getByPet']);
    Route::get('pets/{pet}/waivers/{type}/history', [\App\Http\Controllers\Api\IntakeFormController::class, 'getHistory']);
    Route::get('waivers/{waiver}', [\App\Http\Controllers\Api\IntakeFormController::class, 'show']);
    Route::post('intake-forms', [\App\Http\Controllers\Api\IntakeFormController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Waitlists
    |--------------------------------------------------------------------------
    */
    Route::apiResource('waitlists', \App\Http\Controllers\Api\WaitlistController::class);
    Route::patch('waitlists/{waitlist}/status', [\App\Http\Controllers\Api\WaitlistController::class, 'updateStatus']);
    Route::post('waitlists/{waitlist}/convert-to-booking', [\App\Http\Controllers\Api\WaitlistController::class, 'convertToBooking']);
    Route::post('waitlists/{waitlist}/send-email-confirmation', [\App\Http\Controllers\Api\WaitlistController::class, 'sendEmailConfirmation']);
    Route::get('waitlists/{waitlist}/audits', [\App\Http\Controllers\Api\WaitlistController::class, 'getHistory']);

    /*
    |--------------------------------------------------------------------------
    | Bookings
    |--------------------------------------------------------------------------
    */
    Route::apiResource('bookings', \App\Http\Controllers\Api\BookingController::class);
    Route::post('bookings/{booking}/rebook', [\App\Http\Controllers\Api\BookingController::class, 'rebook']);
    Route::patch('bookings/{booking}/status', [\App\Http\Controllers\Api\BookingController::class, 'updateStatus']);
    Route::get('bookings/{booking}/audits', [\App\Http\Controllers\Api\BookingController::class, 'getHistory']);
    Route::get('bookings/{booking}/detail-audits', [\App\Http\Controllers\Api\BookingController::class, 'getDetailHistory']);
    Route::get('bookings/{booking}/inventory-audits', [\App\Http\Controllers\Api\BookingController::class, 'getInventoryHistory']);
    Route::get('bookings/{booking}/stock-usages', [\App\Http\Controllers\Api\BookingController::class, 'getStockUsages']);
    Route::get('bookings/{booking}/invoice', [\App\Http\Controllers\Api\BookingController::class, 'generateInvoice']);
    Route::get('bookings/{booking}/receipt', [\App\Http\Controllers\Api\BookingController::class, 'generateReceipt']);
    Route::post('bookings/{booking}/send-invoice', [\App\Http\Controllers\Api\BookingController::class, 'sendInvoice']);
    Route::post('bookings/{booking}/send-receipt', [\App\Http\Controllers\Api\BookingController::class, 'sendReceipt']);
    Route::post('bookings/{booking}/send-sms-confirmation', [\App\Http\Controllers\Api\BookingController::class, 'sendSmsConfirmation']);
    Route::post('bookings/{booking}/send-email-confirmation', [\App\Http\Controllers\Api\BookingController::class, 'sendEmailConfirmation']);

    /*
    |--------------------------------------------------------------------------
    | Recurring Bookings
    |--------------------------------------------------------------------------
    */
    Route::apiResource('booking-recurrings', \App\Http\Controllers\Api\BookingRecurringController::class);
    Route::patch('booking-recurrings/{bookingRecurring}/cancel', [\App\Http\Controllers\Api\BookingRecurringController::class, 'cancel']);
    Route::get('booking-recurrings/{bookingRecurring}/audits', [\App\Http\Controllers\Api\BookingRecurringController::class, 'getHistory']);
    Route::get('booking-recurrings/{bookingRecurring}/detail-audits', [\App\Http\Controllers\Api\BookingRecurringController::class, 'getDetailHistory']);

    /*
    |--------------------------------------------------------------------------
    | Blockouts
    |--------------------------------------------------------------------------
    */
    Route::apiResource('blockouts', \App\Http\Controllers\Api\BlockoutController::class);
    Route::get('blockouts/{blockout}/audits', [\App\Http\Controllers\Api\BlockoutController::class, 'getHistory']);

    /*
    |--------------------------------------------------------------------------
    | Recurring Blockouts
    |--------------------------------------------------------------------------
    */
    Route::apiResource('blockout-recurrings', \App\Http\Controllers\Api\BlockoutRecurringController::class);
    Route::get('blockout-recurrings/{blockoutRecurring}/audits', [\App\Http\Controllers\Api\BlockoutRecurringController::class, 'getHistory']);

    /*
    |--------------------------------------------------------------------------
    | Calendar Events
    |--------------------------------------------------------------------------
    */
    Route::apiResource('calendar-events', \App\Http\Controllers\Api\CalendarEventController::class);
    Route::get('calendar-events/month', [\App\Http\Controllers\Api\CalendarEventController::class, 'getByMonth']);
    Route::post('calendar-events/sync', [\App\Http\Controllers\Api\CalendarEventController::class, 'syncEvents']);

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    */
    Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);
    Route::get('service-prices', [\App\Http\Controllers\Api\ServicePriceController::class, 'index']);
    Route::post('service-prices', [\App\Http\Controllers\Api\ServicePriceController::class, 'updateAll']);
    Route::get('company-services', [\App\Http\Controllers\Api\CompanyServiceController::class, 'index']);
    Route::post('company-services', [\App\Http\Controllers\Api\CompanyServiceController::class, 'updateAll']);

    /*
    |--------------------------------------------------------------------------
    | Service Inventory Usage (Global definitions)
    |--------------------------------------------------------------------------
    */
    Route::apiResource('service-inventory-usages', \App\Http\Controllers\Api\ServiceInventoryUsageController::class)->except(['show', 'create', 'edit']);
    Route::get('service-inventory-usages/{serviceId}/history', [\App\Http\Controllers\Api\ServiceInventoryUsageController::class, 'history']);

    /*
    |--------------------------------------------------------------------------
    | Company Service Inventory Usage (Company-specific)
    |--------------------------------------------------------------------------
    */
    Route::apiResource('company-service-inventory-usages', \App\Http\Controllers\Api\CompanyServiceInventoryUsageController::class)->except(['show', 'create', 'edit']);

    /*
    |--------------------------------------------------------------------------
    | Inventory Items
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->group(function () {
        // Categories
        Route::get('categories', [\App\Http\Controllers\Api\InventoryCategoryController::class, 'index']);
        Route::post('categories', [\App\Http\Controllers\Api\InventoryCategoryController::class, 'store']);
        Route::put('categories/{inventoryCategory}', [\App\Http\Controllers\Api\InventoryCategoryController::class, 'update']);
        Route::delete('categories/{inventoryCategory}', [\App\Http\Controllers\Api\InventoryCategoryController::class, 'destroy']);
        
        // Items
        Route::get('items', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
        Route::post('items', [\App\Http\Controllers\Api\InventoryController::class, 'store']);
        Route::put('items/{inventoryItem}', [\App\Http\Controllers\Api\InventoryController::class, 'update']);
        Route::delete('items/{inventoryItem}', [\App\Http\Controllers\Api\InventoryController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Orders
    |--------------------------------------------------------------------------
    */
    Route::apiResource('inventory/orders', \App\Http\Controllers\Api\InventoryOrderController::class);

    /*
    |--------------------------------------------------------------------------
    | Stock Take
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock-take')->group(function () {
        Route::get('last/{categoryId}', [\App\Http\Controllers\Api\StockTakeController::class, 'getLast']);
        Route::get('history/{categoryId}', [\App\Http\Controllers\Api\StockTakeController::class, 'getHistory']);
        Route::get('current-soh/{categoryId}', [\App\Http\Controllers\Api\StockTakeController::class, 'getCurrentSoh']);
        Route::post('/', [\App\Http\Controllers\Api\StockTakeController::class, 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Income & Expense Categories
    |--------------------------------------------------------------------------
    */
    Route::apiResource('income-categories', \App\Http\Controllers\Api\IncomeCategoryController::class);
    Route::apiResource('expense-categories', \App\Http\Controllers\Api\ExpenseCategoryController::class);

    /*
    |--------------------------------------------------------------------------
    | Incomes
    |--------------------------------------------------------------------------
    */
    Route::apiResource('incomes', \App\Http\Controllers\Api\IncomeController::class);
    Route::get('incomes/{income}/audits', [\App\Http\Controllers\Api\IncomeController::class, 'getHistory']);
    Route::apiResource('recurring-incomes', \App\Http\Controllers\Api\RecurringIncomeController::class);

    /*
    |--------------------------------------------------------------------------
    | Expenses
    |--------------------------------------------------------------------------
    */
    Route::apiResource('expenses', \App\Http\Controllers\Api\ExpenseController::class);
    Route::get('expenses/{expense}/audits', [\App\Http\Controllers\Api\ExpenseController::class, 'getHistory']);
    Route::apiResource('recurring-expenses', \App\Http\Controllers\Api\RecurringExpenseController::class);

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    */
    Route::apiResource('documents', \App\Http\Controllers\Api\DocumentController::class);

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('booking', [\App\Http\Controllers\Api\BookingReportController::class, 'index']);
        Route::get('service', [\App\Http\Controllers\Api\ServiceReportController::class, 'index']);
        Route::get('suburb', [\App\Http\Controllers\Api\SuburbReportController::class, 'index']);
        Route::get('customer', [\App\Http\Controllers\Api\CustomerReportController::class, 'index']);
        Route::get('customer/unbooked', [\App\Http\Controllers\Api\UnbookedCustomerReportController::class, 'index']);
        Route::get('benchmarking', [\App\Http\Controllers\Api\BenchmarkingController::class, 'index']);
        Route::get('income', [\App\Http\Controllers\Api\IncomeReportController::class, 'index']);
        Route::get('tracking', [\App\Http\Controllers\Api\ReportController::class, 'tracking']);
        Route::get('gst', [\App\Http\Controllers\Api\ReportController::class, 'gstSummary']);
        Route::get('profit-loss', [\App\Http\Controllers\Api\ReportController::class, 'profitLoss']);
    });

    /*
    |--------------------------------------------------------------------------
    | Communication History
    |--------------------------------------------------------------------------
    */
    Route::prefix('communication')->group(function () {
        Route::get('sms-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsIndex']);
        Route::post('sms-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsStore']);
        Route::get('sms-history/{smsHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsShow']);
        Route::delete('sms-history/{smsHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'smsDestroy']);
        Route::get('email-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailIndex']);
        Route::post('email-history', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailStore']);
        Route::get('email-history/{emailHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailShow']);
        Route::delete('email-history/{emailHistory}', [\App\Http\Controllers\Api\CommunicationHistoryController::class, 'emailDestroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | SMS Credits
    |--------------------------------------------------------------------------
    */
    Route::prefix('sms-credits')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SmsCreditController::class, 'index']);
        Route::post('purchase', [\App\Http\Controllers\Api\SmsCreditController::class, 'purchase']);
        Route::get('history', [\App\Http\Controllers\Api\SmsCreditController::class, 'history']);
    });

    /*
    |--------------------------------------------------------------------------
    | Forum - Threads
    |--------------------------------------------------------------------------
    */
    Route::prefix('forum')->group(function () {
        Route::get('threads', [\App\Http\Controllers\Api\ForumController::class, 'index']);
        Route::post('threads', [\App\Http\Controllers\Api\ForumController::class, 'store']);
        Route::get('threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'show']);
        Route::delete('threads/{forumThread}', [\App\Http\Controllers\Api\ForumController::class, 'destroy']);
        Route::post('threads/{forumThread}/comments', [\App\Http\Controllers\Api\ForumController::class, 'addComment']);
        Route::post('threads/{forumThread}/like', [\App\Http\Controllers\Api\ForumController::class, 'like']);
        Route::get('notifications', [\App\Http\Controllers\Api\ForumController::class, 'notifications']);
        Route::post('notifications/read-all', [\App\Http\Controllers\Api\ForumController::class, 'markAllNotificationsAsRead']);
        Route::post('notifications/{forumNotification}/read', [\App\Http\Controllers\Api\ForumController::class, 'markNotificationAsRead']);
        Route::post('comments/{forumComment}/like', [\App\Http\Controllers\Api\ForumController::class, 'likeComment']);
        Route::post('comments/{forumComment}/reply', [\App\Http\Controllers\Api\ForumController::class, 'replyToComment']);
    });

    /*
    |--------------------------------------------------------------------------
    | Forum - Groups
    |--------------------------------------------------------------------------
    */
    Route::prefix('forum/groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ForumGroupController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\ForumGroupController::class, 'store']);
        Route::get('{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'show']);
        Route::put('{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'update']);
        Route::delete('{forumGroup}', [\App\Http\Controllers\Api\ForumGroupController::class, 'destroy']);
        Route::post('{forumGroup}/join', [\App\Http\Controllers\Api\ForumGroupController::class, 'join']);
        Route::post('{forumGroup}/leave', [\App\Http\Controllers\Api\ForumGroupController::class, 'leave']);
        Route::get('{forumGroup}/members', [\App\Http\Controllers\Api\ForumGroupController::class, 'members']);
    });

    /*
    |--------------------------------------------------------------------------
    | News
    |--------------------------------------------------------------------------
    */
    Route::apiResource('news', \App\Http\Controllers\Api\NewsController::class);
    Route::patch('news/{news}/publish', [\App\Http\Controllers\Api\NewsController::class, 'publish']);

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        Route::get('preferences', [\App\Http\Controllers\Api\SettingsController::class, 'getPreferences']);
        Route::post('preferences', [\App\Http\Controllers\Api\SettingsController::class, 'savePreferences']);
        Route::get('income-templates', [\App\Http\Controllers\Api\SettingsController::class, 'getIncomeTemplates']);
        Route::post('income-templates', [\App\Http\Controllers\Api\SettingsController::class, 'saveIncomeTemplates']);
        Route::get('calendar', [\App\Http\Controllers\Api\SettingsController::class, 'getCalendarSettings']);
        Route::post('calendar', [\App\Http\Controllers\Api\SettingsController::class, 'saveCalendarSettings']);
        Route::get('cancellation-policy', [\App\Http\Controllers\Api\SettingsController::class, 'getCancellationPolicy']);
        Route::post('cancellation-policy', [\App\Http\Controllers\Api\SettingsController::class, 'saveCancellationPolicy']);
        Route::get('reminder', [\App\Http\Controllers\Api\SettingsController::class, 'getReminderSettings']);
        Route::post('reminder', [\App\Http\Controllers\Api\SettingsController::class, 'saveReminderSettings']);
        Route::get('app-calendar', [\App\Http\Controllers\Api\SettingsController::class, 'getAppCalendarSettings']);
        Route::post('app-calendar', [\App\Http\Controllers\Api\SettingsController::class, 'saveAppCalendarSettings']);
    });

    /*
    |--------------------------------------------------------------------------
    | Website Settings
    |--------------------------------------------------------------------------
    */
    Route::get('website-settings', [\App\Http\Controllers\Api\WebsiteSettingsController::class, 'show']);
    Route::put('website-settings', [\App\Http\Controllers\Api\WebsiteSettingsController::class, 'update']);

    /*
    |--------------------------------------------------------------------------
    | Cancellation Policies
    |--------------------------------------------------------------------------
    */
    Route::get('cancellation-policies', [\App\Http\Controllers\Api\CancellationPoliciesController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Version Updates
    |--------------------------------------------------------------------------
    */
    Route::prefix('version-updates')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VersionUpdateController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\VersionUpdateController::class, 'store']);
        Route::put('{versionUpdate}', [\App\Http\Controllers\Api\VersionUpdateController::class, 'update']);
        Route::delete('{versionUpdate}', [\App\Http\Controllers\Api\VersionUpdateController::class, 'destroy']);
    });
});
