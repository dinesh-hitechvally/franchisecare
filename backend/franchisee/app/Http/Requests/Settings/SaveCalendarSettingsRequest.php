<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SaveCalendarSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'show_booking_total' => 'boolean',
            'show_customer_name' => 'boolean',
            'show_customer_address' => 'boolean',
            'show_pet_name' => 'boolean',
            'show_pet_breed' => 'boolean',
            'show_services_name' => 'boolean',
            'show_time' => 'boolean',
            'show_cancellation_policy' => 'boolean',
            'display_order' => 'nullable|array',
        ];
    }
}
