<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SaveAppCalendarSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'show_customer_name' => 'boolean',
            'show_customer_address' => 'boolean',
            'show_booking_total' => 'boolean',
            'show_time' => 'boolean',
            'show_pet_name' => 'boolean',
            'show_services_name' => 'boolean',
            'show_pet_breed' => 'boolean',
            'display_order' => 'nullable|array',
        ];
    }
}
