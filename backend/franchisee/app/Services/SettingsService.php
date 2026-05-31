<?php

namespace App\Services;

use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Contracts\Services\SettingsServiceInterface;
use App\Models\Preference;
use App\Models\IncomeTemplate;
use App\Models\CalendarSetting;
use App\Models\CancellationPolicy;
use App\Models\ReminderSetting;
use App\Models\AppCalendarSetting;
use Illuminate\Support\Facades\Auth;

class SettingsService implements SettingsServiceInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): ?int
    {
        $user = Auth::user();
        return $user?->company_id ?? $user?->franchise_id;
    }

    // Preferences
    public function getPreferences()
    {
        $companyId = $this->getCompanyId();
        $preferences = $this->repository->getPreferences($companyId);

        if (!$preferences) {
            $preferences = new Preference([
                'company_id' => $companyId,
                'display_customer_notes' => true,
                'hide_expired_bookings' => true,
                'hide_booking_cash_notifications' => true,
                'hide_past_bookings' => false,
                'filter_services_by_pet_size' => false,
                'display_booking_end_time' => true,
                'show_address_in_invoice' => true,
                'show_personal_phone' => true,
                'time_format' => 'H:i',
                'date_format' => 'd/m/Y',
            ]);
        }

        return $preferences;
    }

    public function savePreferences(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->savePreferences($companyId, $data);
    }

    // Income Templates
    public function getIncomeTemplates()
    {
        $companyId = $this->getCompanyId();
        $templates = $this->repository->getIncomeTemplates($companyId);

        if (!$templates) {
            $templates = new IncomeTemplate([
                'company_id' => $companyId,
                'income_title_template' => 'Income from {{customername}} - {{date}}',
                'invoice_statement_template' => 'income from {{customername}} - {{date}}',
            ]);
        }

        return $templates;
    }

    public function saveIncomeTemplates(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->saveIncomeTemplates($companyId, $data);
    }

    // Calendar Settings
    public function getCalendarSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = $this->repository->getCalendarSettings($companyId);

        if (!$settings) {
            $settings = new CalendarSetting([
                'company_id' => $companyId,
                'show_booking_total' => true,
                'show_customer_name' => true,
                'show_customer_address' => true,
                'show_pet_name' => true,
                'show_pet_breed' => true,
                'show_services_name' => true,
                'show_time' => true,
                'show_cancellation_policy' => true,
                'display_order' => null,
            ]);
        }

        return $settings;
    }

    public function saveCalendarSettings(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->saveCalendarSettings($companyId, $data);
    }

    // Cancellation Policy
    public function getCancellationPolicy()
    {
        $companyId = $this->getCompanyId();
        $policy = $this->repository->getCancellationPolicy($companyId);

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

        return $policy;
    }

    public function saveCancellationPolicy(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->saveCancellationPolicy($companyId, $data);
    }

    // Reminder Settings
    public function getReminderSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = $this->repository->getReminderSettings($companyId);

        if (!$settings) {
            $settings = new ReminderSetting([
                'company_id' => $companyId,
                'reminder_method' => 'email-only',
                'send_before_hours' => 24,
            ]);
        }

        return $settings;
    }

    public function saveReminderSettings(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->saveReminderSettings($companyId, $data);
    }

    // App Calendar Settings
    public function getAppCalendarSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = $this->repository->getAppCalendarSettings($companyId);

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

        return $settings;
    }

    public function saveAppCalendarSettings(array $data)
    {
        $companyId = $this->getCompanyId();
        return $this->repository->saveAppCalendarSettings($companyId, $data);
    }
}
