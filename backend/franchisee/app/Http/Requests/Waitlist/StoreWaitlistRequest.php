<?php

namespace App\Http\Requests\Waitlist;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaitlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'required|string',
            'end_time' => 'nullable|string',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'in:active,cancelled,completed,expired',
            'total' => 'required|numeric',
            'duration' => 'required|integer',
            'notes' => 'nullable|string',
            'services' => 'required|array',
            'services.*.item_id' => 'required|exists:customer_items,id',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
        ];
    }

    public function waitlistData(): array
    {
        return $this->validated();
    }
}
