<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\SettingsServiceInterface;
use App\Http\Requests\Settings\SavePreferencesRequest;
use App\Http\Requests\Settings\SaveIncomeTemplatesRequest;
use App\Http\Requests\Settings\SaveCalendarSettingsRequest;
use App\Http\Requests\Settings\SaveCancellationPolicyRequest;
use App\Http\Requests\Settings\SaveReminderSettingsRequest;
use App\Http\Requests\Settings\SaveAppCalendarSettingsRequest;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsServiceInterface $settingsService
    ) {}

    // Preferences
    public function getPreferences(): JsonResponse
    {
        return response()->json($this->settingsService->getPreferences());
    }

    public function savePreferences(SavePreferencesRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->savePreferences($request->validated()));
    }

    // Income Templates
    public function getIncomeTemplates(): JsonResponse
    {
        return response()->json($this->settingsService->getIncomeTemplates());
    }

    public function saveIncomeTemplates(SaveIncomeTemplatesRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->saveIncomeTemplates($request->validated()));
    }

    // Calendar Settings
    public function getCalendarSettings(): JsonResponse
    {
        return response()->json($this->settingsService->getCalendarSettings());
    }

    public function saveCalendarSettings(SaveCalendarSettingsRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->saveCalendarSettings($request->validated()));
    }

    // Cancellation Policy
    public function getCancellationPolicy(): JsonResponse
    {
        return response()->json($this->settingsService->getCancellationPolicy());
    }

    public function saveCancellationPolicy(SaveCancellationPolicyRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->saveCancellationPolicy($request->validated()));
    }

    // Reminder Settings
    public function getReminderSettings(): JsonResponse
    {
        return response()->json($this->settingsService->getReminderSettings());
    }

    public function saveReminderSettings(SaveReminderSettingsRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->saveReminderSettings($request->validated()));
    }

    // App Calendar Settings
    public function getAppCalendarSettings(): JsonResponse
    {
        return response()->json($this->settingsService->getAppCalendarSettings());
    }

    public function saveAppCalendarSettings(SaveAppCalendarSettingsRequest $request): JsonResponse
    {
        return response()->json($this->settingsService->saveAppCalendarSettings($request->validated()));
    }
}
        );

        return response()->json($settings);
    }

    // Cancellation Policy
    public function getCancellationPolicy()
    {
        $companyId = $this->getCompanyId();
        $policy = CancellationPolicy::where('company_id', $companyId)->first();

        if (!$policy) {
            $policy = new CancellationPolicy([
                'company_id' => $companyId,
                'attach_policy' => false,
                'cancel_before_unit' => 'hours',
                'cancel_before_value' => 24,
                'cancellation_fee_value' => 0,
                'penalty_type' => 'percent',
            ]);
        }

        return response()->json($policy);
    }

    public function saveCancellationPolicy(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'attach_policy' => 'boolean',
            'cancel_before_unit' => 'in:hours,cutoff',
            'cancel_before_value' => 'integer',
            'cancel_cutoff_time' => 'nullable|date_format:H:i',
            'cancellation_fee_value' => 'numeric',
            'penalty_type' => 'in:percent,fixed',
            'policy_id' => 'nullable|integer',
            'policy_text' => 'nullable|string',
        ]);

        $policy = CancellationPolicy::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return response()->json($policy);
    }

    // Reminder Settings
    public function getReminderSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = ReminderSetting::where('company_id', $companyId)->first();

        if (!$settings) {
            $settings = new ReminderSetting([
                'company_id' => $companyId,
                'reminder_method' => 'email-only',
                'send_before_hours' => 24,
            ]);
        }

        return response()->json($settings);
    }

    public function saveReminderSettings(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'reminder_method' => 'in:no-send,email-sms,email-only,sms-only,email-if-found,sms-if-no-mobile',
            'send_before_hours' => 'integer',
        ]);

        $settings = ReminderSetting::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return response()->json($settings);
    }

    // App Calendar Settings
    public function getAppCalendarSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = AppCalendarSetting::where('company_id', $companyId)->first();

        if (!$settings) {
            $settings = new AppCalendarSetting([
                'company_id' => $companyId,
                'show_customer_name' => true,
                'show_customer_address' => true,
                'show_booking_total' => true,
                'show_time' => true,
                'show_pet_name' => true,
                'show_services_name' => true,
                'show_pet_breed' => true,
            ]);
        }

        return response()->json($settings);
    }

    public function saveAppCalendarSettings(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'show_customer_name' => 'boolean',
            'show_customer_address' => 'boolean',
            'show_booking_total' => 'boolean',
            'show_time' => 'boolean',
            'show_pet_name' => 'boolean',
            'show_services_name' => 'boolean',
            'show_pet_breed' => 'boolean',
            'display_order' => 'nullable|array',
        ]);

        $settings = AppCalendarSetting::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return response()->json($settings);
    }
}
