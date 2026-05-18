<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Preference;
use App\Models\IncomeTemplate;
use App\Models\CalendarSetting;
use App\Models\CancellationPolicy;
use App\Models\ReminderSetting;
use App\Models\AppCalendarSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    private function getCompanyId()
    {
        $user = Auth::user();
        return $user?->company_id ?? $user?->franchise_id;
    }

    // Preferences
    public function getPreferences()
    {
        $companyId = $this->getCompanyId();
        $preferences = Preference::where('company_id', $companyId)->first();

        if (!$preferences) {
            // Return defaults if not found
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
                'time_format' => 'H:i', // default
                'date_format' => 'd/m/Y', // default
            ]);
        }

        return response()->json($preferences);
    }

    public function savePreferences(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'display_customer_notes' => 'boolean',
            'hide_expired_bookings' => 'boolean',
            'hide_booking_cash_notifications' => 'boolean',
            'hide_past_bookings' => 'boolean',
            'filter_services_by_pet_size' => 'boolean',
            'display_booking_end_time' => 'boolean',
            'show_address_in_invoice' => 'boolean',
            'show_personal_phone' => 'boolean',
            'time_format' => 'string',
            'date_format' => 'string',
        ]);

        $preferences = Preference::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return response()->json($preferences);
    }

    // Income Templates
    public function getIncomeTemplates()
    {
        $companyId = $this->getCompanyId();
        $templates = IncomeTemplate::where('company_id', $companyId)->first();

        if (!$templates) {
            $templates = new IncomeTemplate([
                'company_id' => $companyId,
                'income_title_template' => 'Income from {{customername}} - {{date}}',
                'invoice_statement_template' => 'income from {{customername}} - {{date}}',
            ]);
        }

        return response()->json($templates);
    }

    public function saveIncomeTemplates(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'income_title_template' => 'nullable|string',
            'invoice_statement_template' => 'nullable|string',
        ]);

        $templates = IncomeTemplate::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );

        return response()->json($templates);
    }

    // Calendar Settings
    public function getCalendarSettings()
    {
        $companyId = $this->getCompanyId();
        $settings = CalendarSetting::where('company_id', $companyId)->first();

        if (!$settings) {
            $settings = new CalendarSetting([
                'company_id' => $companyId,
                'show_booking_total' => true,
                'show_customer_name' => true,
                'show_customer_address' => true,
                'show_pet_name' => true,
                'show_pet_breed' => true,
                'show_services_name' => true,
            ]);
        }

        return response()->json($settings);
    }

    public function saveCalendarSettings(Request $request)
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'show_booking_total' => 'boolean',
            'show_customer_name' => 'boolean',
            'show_customer_address' => 'boolean',
            'show_pet_name' => 'boolean',
            'show_pet_breed' => 'boolean',
            'show_services_name' => 'boolean',
        ]);

        $settings = CalendarSetting::updateOrCreate(
            ['company_id' => $companyId],
            $validated
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
