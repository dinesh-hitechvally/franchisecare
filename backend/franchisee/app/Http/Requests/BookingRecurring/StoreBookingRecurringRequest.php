<?php

namespace App\Http\Requests\BookingRecurring;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRecurringRequest extends FormRequest
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
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'frequency' => 'required|in:daily,weekly,fortnightly,monthly,yearly',
            'interval' => 'required|integer|min:1|max:12',
            'repeat_until' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,paused,cancelled',
            'details' => 'nullable|array',
            'details.*.item_id' => 'required|exists:customer_items,id',
            'details.*.service_id' => 'required|exists:services,id',
            'details.*.price' => 'nullable|numeric|min:0',
        ];
    }
}
