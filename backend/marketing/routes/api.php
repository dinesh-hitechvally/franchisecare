<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApiV1Controller;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonFieldController;
use App\Http\Controllers\PeopleListController;
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
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\IntegrationOAuthController;
use App\Http\Controllers\FormEmbedController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\PushPublicController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------- public
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('t/o/{token}.gif', [TrackingController::class, 'open']);
Route::get('t/c/{token}', [TrackingController::class, 'click']);
Route::get('unsubscribe/{token}', [TrackingController::class, 'unsubscribe']);
Route::post('unsubscribe/{token}', [TrackingController::class, 'unsubscribe'])->middleware('throttle:30,1'); // the one-click button in mail apps (List-Unsubscribe-Post)
Route::get('m/{token}/{name?}', [MediaController::class, 'file'])->middleware('throttle:300,1'); // a library file by its unguessable token
Route::get('oauth/mail/{provider}/callback', [MailOAuthController::class, 'callback']); // the provider sends the browser back here
Route::get('oauth/integrations/callback', [IntegrationOAuthController::class, 'callback']); // a platform sends the browser back here after the person approves
Route::post('oauth/integrations/woocommerce', [IntegrationOAuthController::class, 'woocommerce'])->middleware('throttle:30,1'); // WooCommerce posts the new keys here
Route::post('public/forms/{slug}', [PublicController::class, 'submit'])->middleware('throttle:20,1');

// the one-script form embed: a website adds forms.js once and a <div class="mate-form" data-form-id="12"> wherever a form should appear
Route::get('embed/forms.js', [FormEmbedController::class, 'script'])->middleware('throttle:300,1');
Route::get('embed/forms/{key}/{id}', [FormEmbedController::class, 'show'])->whereNumber('id')->middleware('throttle:300,1');

// push notifications: what a website talks to (identified by the company's public site key)
Route::get('push/sdk.js', [PushPublicController::class, 'sdk'])->middleware('throttle:120,1');
Route::get('push/sw.js', [PushPublicController::class, 'serviceWorker'])->middleware('throttle:120,1');
Route::get('push/worker.js', [PushPublicController::class, 'worker'])->middleware('throttle:300,1');   // the same worker, inline: forms and landing pages on this app register it
Route::get('push/config', [PushPublicController::class, 'config'])->middleware('throttle:120,1');
Route::post('push/subscribe', [PushPublicController::class, 'subscribe'])->middleware('throttle:30,1');
Route::post('push/unsubscribe', [PushPublicController::class, 'unsubscribe'])->middleware('throttle:30,1');
Route::get('push/c/{token}', [PushPublicController::class, 'click'])->middleware('throttle:120,1'); // a tap on a notification

// ---------------------------------------------------------------- integration API (API-key authenticated)
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('people', [ApiV1Controller::class, 'show']);
    Route::post('people', [ApiV1Controller::class, 'upsert']);
    Route::post('people/unsubscribe', [ApiV1Controller::class, 'unsubscribe']);
    Route::post('push/devices', [ApiV1Controller::class, 'pushDevice']);       // an Android / iOS app registers its Firebase token
    Route::delete('push/devices', [ApiV1Controller::class, 'pushDeviceRemove']); // ... or removes it (logout, uninstall)
});

// ---------------------------------------------------------------- dashboard API (all data is scoped to the user's company)
Route::middleware(['auth:sanctum', 'no.integration', 'log.activity'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('account', [AccountController::class, 'update']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [LogController::class, 'dashboard']);

    // Push notifications (settings, connections, registered devices)
    Route::get('push/settings', [PushController::class, 'show']);
    Route::put('push/settings', [PushController::class, 'update']);
    Route::post('push/web-keys/rotate', [PushController::class, 'rotateKeys'])->middleware('throttle:10,1');
    Route::put('push/fcm', [PushController::class, 'saveFcm'])->middleware('throttle:20,1');
    Route::delete('push/fcm', [PushController::class, 'removeFcm']);
    Route::get('push/audience', [PushController::class, 'audience']);
    Route::get('push/devices', [PushController::class, 'devices']);
    Route::post('push/devices/{device}/test', [PushController::class, 'testDevice'])->middleware('throttle:20,1');
    Route::delete('push/devices/{device}', [PushController::class, 'destroyDevice']);

    Route::post('attachments', [AttachmentController::class, 'store'])->middleware('throttle:60,1');
    Route::post('attachments/from-media', [AttachmentController::class, 'fromMedia'])->middleware('throttle:60,1');
    Route::post('media', [MediaController::class, 'store'])->middleware('throttle:120,1');
    Route::post('media/import', [MediaController::class, 'importOnline'])->middleware('throttle:60,1');
    Route::apiResource('media', MediaController::class)->only(['index', 'update', 'destroy'])->parameters(['media' => 'media']);

    // Audience
    Route::get('people/export', [PersonController::class, 'export']);
    Route::post('people/import', [PersonController::class, 'import']);
    Route::post('people/bulk', [PersonController::class, 'bulk']);
    Route::apiResource('people', PersonController::class)->except('show');
    Route::apiResource('lists', PeopleListController::class)->except('show')->parameters(['lists' => 'list']);
    Route::apiResource('person-fields', PersonFieldController::class)->except('show')->parameters(['person-fields' => 'field']);
    Route::apiResource('tags', TagController::class)->except('show');
    Route::get('segments/fields', [SegmentController::class, 'fields']);
    Route::post('segments/preview', [SegmentController::class, 'preview']);
    Route::apiResource('segments', SegmentController::class)->except('show');
    Route::get('subscriptions', [SubscriptionController::class, 'index']);
    Route::post('subscriptions/bulk', [SubscriptionController::class, 'bulk']);

    // Campaigns
    Route::post('campaigns/preview', [CampaignController::class, 'preview']);
    Route::get('merge-tags', [CampaignController::class, 'mergeTags']);
    Route::apiResource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send']);
    Route::post('campaigns/{campaign}/unschedule', [CampaignController::class, 'unschedule']);
    Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel']);
    Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate']);
    Route::get('campaigns/{campaign}/check', [CampaignController::class, 'check']);
    Route::post('campaigns/{campaign}/test', [CampaignController::class, 'test']);
    Route::get('reports/campaigns', [ReportController::class, 'campaigns']);
    Route::get('reports/campaigns/{campaign}', [ReportController::class, 'campaign']);
    Route::get('reports/campaigns/{campaign}/deliveries', [ReportController::class, 'deliveries']);
    Route::get('reports/campaigns/{campaign}/export', [ReportController::class, 'export']);
    Route::get('logs', [LogController::class, 'index']);
    Route::get('logs/{channel}/{id}', [LogController::class, 'show'])->whereNumber('id');

    // Content
    Route::post('templates/{template}/duplicate', [TemplateController::class, 'duplicate']);
    Route::apiResource('templates', TemplateController::class)->except('show');
    Route::apiResource('layouts', LayoutController::class)->only(['index', 'store', 'destroy']);
    Route::get('submissions', [FormSubmissionController::class, 'all']);
    Route::get('forms/{form}/submissions', [FormSubmissionController::class, 'index']);
    Route::get('forms/{form}/submissions/export', [FormSubmissionController::class, 'export']);
    Route::delete('forms/{form}/submissions/{submission}', [FormSubmissionController::class, 'destroy']);
    Route::post('forms/{form}/duplicate', [FormController::class, 'duplicate']);
    Route::apiResource('forms', FormController::class);
    Route::post('landing-pages/{page}/duplicate', [LandingPageController::class, 'duplicate']);
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
    Route::get('analytics/people', [ReportController::class, 'personAnalytics']);
    Route::get('analytics/conversions', [ReportController::class, 'conversionAnalytics']);
    Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('analytics/email', [AnalyticsController::class, 'email']);
    Route::get('analytics/sms', [AnalyticsController::class, 'sms']);
    Route::get('analytics/delivery', [AnalyticsController::class, 'delivery']);
    Route::get('analytics/automation', [AnalyticsController::class, 'automation']);

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
        Route::get('webhook-deliveries', [WebhookController::class, 'allDeliveries']);
        Route::post('webhooks/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries']);
        Route::apiResource('webhooks', WebhookController::class)->except('show');

        // connected platforms (Shopify, WooCommerce, WordPress, Magento): their customers are imported as people
        Route::get('integrations/providers', [IntegrationController::class, 'providers']);
        Route::get('integration-syncs', [IntegrationController::class, 'allSyncs']);
        Route::post('integrations/{integration}/check', [IntegrationController::class, 'check'])->middleware('throttle:20,1');
        Route::post('integrations/{integration}/sync', [IntegrationController::class, 'sync'])->middleware('throttle:20,1');
        Route::get('integrations/{integration}/syncs', [IntegrationController::class, 'syncs']);
        Route::post('integrations', [IntegrationController::class, 'store'])->middleware('throttle:20,1');
        Route::post('integrations/oauth/start', [IntegrationOAuthController::class, 'start'])->middleware('throttle:20,1');
        Route::apiResource('integrations', IntegrationController::class)->only(['index', 'update', 'destroy']);

        Route::apiResource('api-keys', ApiKeyController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('users', UserController::class)->except('show');
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('history', [HistoryController::class, 'index']);
        Route::get('history/types', [HistoryController::class, 'types']);
        Route::get('history/{type}/{id}', [HistoryController::class, 'record'])->whereNumber('id');
        Route::get('history/{type}/entry/{historyId}', [HistoryController::class, 'entry'])->whereNumber('historyId');
    });
});
