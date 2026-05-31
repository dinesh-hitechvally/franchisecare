<?php

namespace App\Contracts\Services;

interface SettingsServiceInterface
{
    // Preferences
    public function getPreferences();
    public function savePreferences(array $data);

    // Income Templates
    public function getIncomeTemplates();
    public function saveIncomeTemplates(array $data);

    // Calendar Settings
    public function getCalendarSettings();
    public function saveCalendarSettings(array $data);

    // Cancellation Policy
    public function getCancellationPolicy();
    public function saveCancellationPolicy(array $data);

    // Reminder Settings
    public function getReminderSettings();
    public function saveReminderSettings(array $data);

    // App Calendar Settings
    public function getAppCalendarSettings();
    public function saveAppCalendarSettings(array $data);
}
