<?php

namespace App\Http\Requests\CalendarEvent;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => 'required|exists:companies,id',
            'event_type' => 'required|in:booking,blockout',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date',
            'end_time' => 'required|string',
            'color' => 'nullable|string',
            'location' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'blockout_id' => 'nullable|exists:blockouts,id',
            'is_recurring' => 'boolean',
        ];
    }
}
