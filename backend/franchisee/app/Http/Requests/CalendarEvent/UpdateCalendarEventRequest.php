<?php

namespace App\Http\Requests\CalendarEvent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => 'sometimes|in:booking,blockout',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date',
            'end_time' => 'sometimes|string',
            'color' => 'nullable|string',
            'location' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'is_recurring' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
