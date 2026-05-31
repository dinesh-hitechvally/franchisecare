<?php

namespace App\Repositories;

use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Models\Preference;
use App\Models\IncomeTemplate;
use App\Models\CalendarSetting;
use App\Models\CancellationPolicy;
use App\Models\ReminderSetting;
use App\Models\AppCalendarSetting;

class SettingsRepository implements SettingsRepositoryInterface
{
    // Preferences
    public function getPreferences(int $companyId)
    {
        return Preference::where('company_id', $companyId)->first();
    }

    public function savePreferences(int $companyId, array $data)
    {
        return Preference::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    // Income Templates
    public function getIncomeTemplates(int $companyId)
    {
        return IncomeTemplate::where('company_id', $companyId)->first();
    }

    public function saveIncomeTemplates(int $companyId, array $data)
    {
        return IncomeTemplate::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    // Calendar Settings
    public function getCalendarSettings(int $companyId)
    {
        return CalendarSetting::where('company_id', $companyId)->first();
    }

    public function saveCalendarSettings(int $companyId, array $data)
    {
        return CalendarSetting::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    // Cancellation Policy
    public function getCancellationPolicy(int $companyId)
    {
        return CancellationPolicy::where('company_id', $companyId)->first();
    }

    public function saveCancellationPolicy(int $companyId, array $data)
    {
        return CancellationPolicy::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    // Reminder Settings
    public function getReminderSettings(int $companyId)
    {
        return ReminderSetting::where('company_id', $companyId)->first();
    }

    public function saveReminderSettings(int $companyId, array $data)
    {
        return ReminderSetting::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }

    // App Calendar Settings
    public function getAppCalendarSettings(int $companyId)
    {
        return AppCalendarSetting::where('company_id', $companyId)->first();
    }

    public function saveAppCalendarSettings(int $companyId, array $data)
    {
        return AppCalendarSetting::updateOrCreate(
            ['company_id' => $companyId],
            $data
        );
    }
}
