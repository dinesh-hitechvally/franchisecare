<?php

namespace App\Http\Requests\BookingRecurring;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRecurringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'frequency' => 'sometimes|required|in:daily,weekly,fortnightly,monthly,yearly',
            'interval' => 'sometimes|required|integer|min:1|max:12',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,paused,cancelled',
            'details' => 'nullable|array',
            'details.*.item_id' => 'required_with:details|exists:customer_items,id',
            'details.*.service_id' => 'required_with:details|exists:services,id',
            'details.*.price' => 'nullable|numeric|min:0',
        ];
    }
}
