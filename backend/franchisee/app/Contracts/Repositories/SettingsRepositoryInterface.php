<?php

namespace App\Contracts\Repositories;

interface SettingsRepositoryInterface
{
    // Preferences
    public function getPreferences(int $companyId);
    public function savePreferences(int $companyId, array $data);

    // Income Templates
    public function getIncomeTemplates(int $companyId);
    public function saveIncomeTemplates(int $companyId, array $data);

    // Calendar Settings
    public function getCalendarSettings(int $companyId);
    public function saveCalendarSettings(int $companyId, array $data);

    // Cancellation Policy
    public function getCancellationPolicy(int $companyId);
    public function saveCancellationPolicy(int $companyId, array $data);

    // Reminder Settings
    public function getReminderSettings(int $companyId);
    public function saveReminderSettings(int $companyId, array $data);

    // App Calendar Settings
    public function getAppCalendarSettings(int $companyId);
    public function saveAppCalendarSettings(int $companyId, array $data);
}
