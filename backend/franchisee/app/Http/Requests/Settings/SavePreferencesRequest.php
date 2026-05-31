<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SavePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
        ];
    }
}
