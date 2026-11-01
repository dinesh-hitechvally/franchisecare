<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApiV1Controller;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactFieldController;
use App\Http\Controllers\ContactListController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MailOAuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- public
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('t/o/{token}.gif', [TrackingController::class, 'open']);
Route::get('t/c/{token}', [TrackingController::class, 'click']);
Route::get('unsubscribe/{token}', [TrackingController::class, 'unsubscribe']);
Route::get('m/{token}/{name?}', [MediaController::class, 'file'])->middleware('throttle:300,1'); // a library file by its unguessable token
Route::get('oauth/mail/{provider}/callback', [MailOAuthController::class, 'callback']); // the provider sends the browser back here
Route::post('public/forms/{slug}', [PublicController::class, 'submit'])->middleware('throttle:20,1');

// ---------------------------------------------------------------- integration API (API-key authenticated)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('contacts', [ApiV1Controller::class, 'show']);
    Route::post('contacts', [ApiV1Controller::class, 'upsert']);
    Route::post('contacts/unsubscribe', [ApiV1Controller::class, 'unsubscribe']);
});

// ---------------------------------------------------------------- dashboard API (all data is scoped to the user's company)
Route::middleware(['auth:sanctum', 'no.integration', 'log.activity'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('account', [AccountController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [LogController::class, 'dashboard']);

    Route::post('attachments', [AttachmentController::class, 'store'])->middleware('throttle:60,1');
    Route::post('media', [MediaController::class, 'store'])->middleware('throttle:120,1');
    Route::post('media/import', [MediaController::class, 'importOnline'])->middleware('throttle:60,1');
    Route::apiResource('media', MediaController::class)->only(['index', 'update', 'destroy'])->parameters(['media' => 'media']);

    // Audience
    Route::post('contacts/import', [ContactController::class, 'import']);
    Route::post('contacts/bulk', [ContactController::class, 'bulk']);
    Route::apiResource('contacts', ContactController::class)->except('show');
    Route::apiResource('lists', ContactListController::class)->except('show')->parameters(['lists' => 'list']);
    Route::apiResource('contact-fields', ContactFieldController::class)->except('show')->parameters(['contact-fields' => 'field']);
    Route::apiResource('tags', TagController::class)->except('show');
    Route::get('segments/fields', [SegmentController::class, 'fields']);
    Route::post('segments/preview', [SegmentController::class, 'preview']);
    Route::apiResource('segments', SegmentController::class)->except('show');
    Route::get('subscriptions', [SubscriptionController::class, 'index']);
    Route::post('subscriptions/bulk', [SubscriptionController::class, 'bulk']);

    // Campaigns
    Route::post('campaigns/preview', [CampaignController::class, 'preview']);
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send']);
    Route::post('campaigns/{campaign}/unschedule', [CampaignController::class, 'unschedule']);
    Route::post('campaigns/{campaign}/test', [CampaignController::class, 'test']);
    Route::get('reports/campaigns', [ReportController::class, 'campaigns']);
    Route::get('reports/campaigns/{campaign}', [ReportController::class, 'campaign']);
    Route::get('reports/campaigns/{campaign}/export', [ReportController::class, 'export']);
    Route::get('logs', [LogController::class, 'index']);
    Route::get('logs/{channel}/{id}', [LogController::class, 'show'])->whereNumber('id');

    // Content
    Route::apiResource('templates', TemplateController::class)->except('show');
    Route::apiResource('layouts', LayoutController::class)->only(['index', 'store', 'destroy']);
    Route::get('forms/{form}/submissions', [FormSubmissionController::class, 'index']);
    Route::get('forms/{form}/submissions/export', [FormSubmissionController::class, 'export']);
    Route::delete('forms/{form}/submissions/{submission}', [FormSubmissionController::class, 'destroy']);
    Route::apiResource('forms', FormController::class);
    Route::apiResource('landing-pages', LandingPageController::class)->except('show')->parameters(['landing-pages' => 'page']);
    Route::get('images/providers', [ImageController::class, 'providers']);
    Route::get('images/search', [ImageController::class, 'search']);

    // Automation
    Route::get('automation/triggers', [WorkflowController::class, 'triggers']);
    Route::get('automation/runs', [WorkflowController::class, 'runs']);
    Route::post('automation/runs/{run}/cancel', [WorkflowController::class, 'cancelRun']);
    Route::get('automation/history', [WorkflowController::class, 'history']);
    Route::post('workflows/{workflow}/toggle', [WorkflowController::class, 'toggle']);
    Route::apiResource('workflows', WorkflowController::class)->except('show');

    // Analytics
    Route::get('analytics/campaigns', [ReportController::class, 'campaignAnalytics']);
    Route::get('analytics/contacts', [ReportController::class, 'contactAnalytics']);
    Route::get('analytics/conversions', [ReportController::class, 'conversionAnalytics']);

    Route::get('settings', [SettingsController::class, 'show']);

    // System settings — every user of the company
    Route::group([], function () {
        Route::put('settings', [SettingsController::class, 'update']);
        Route::get('config/email', [ConfigController::class, 'email']);
        Route::put('config/email', [ConfigController::class, 'updateEmail']);
        Route::get('config/email/suggest', [ConfigController::class, 'suggest'])->middleware('throttle:30,1');
        Route::get('oauth/mail/{provider}/start', [MailOAuthController::class, 'start']);
        Route::put('config/email/connections/{key}', [ConfigController::class, 'saveConnection']);
        Route::delete('config/email/connections/{key}', [ConfigController::class, 'disconnect']);
        Route::post('config/email/test', [ConfigController::class, 'testEmail']);
        Route::get('config/sms', [ConfigController::class, 'sms']);
        Route::put('config/sms', [ConfigController::class, 'updateSms']);
        Route::put('config/sms/connections/{key}', [ConfigController::class, 'saveSmsConnection']);
        Route::delete('config/sms/connections/{key}', [ConfigController::class, 'disconnectSms']);
        Route::post('config/sms/test', [ConfigController::class, 'testSms']);

        Route::get('webhooks/events', [WebhookController::class, 'events']);
        Route::post('webhooks/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries']);
        Route::apiResource('webhooks', WebhookController::class)->except('show');

        Route::apiResource('api-keys', ApiKeyController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('users', UserController::class)->except('show');
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('history', [HistoryController::class, 'index']);
        Route::get('history/types', [HistoryController::class, 'types']);
        Route::get('history/{type}/{id}', [HistoryController::class, 'record'])->whereNumber('id');
    });
});
