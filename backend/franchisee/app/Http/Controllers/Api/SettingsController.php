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
