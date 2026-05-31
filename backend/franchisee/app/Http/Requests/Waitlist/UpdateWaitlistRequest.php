<?php

namespace App\Http\Requests\Waitlist;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWaitlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'sometimes|string',
            'end_time' => 'nullable|string',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'in:active,cancelled,completed,expired',
            'total' => 'sometimes|numeric',
            'duration' => 'sometimes|integer',
            'notes' => 'nullable|string',
            'services' => 'sometimes|array',
            'services.*.item_id' => 'required_with:services|exists:customer_items,id',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
        ];
    }

    public function waitlistData(): array
    {
        return $this->validated();
    }
}
