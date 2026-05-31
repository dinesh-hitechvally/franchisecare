<?php

namespace App\Http\Requests\BlockoutRecurring;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockoutRecurringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date',
            'end_time' => 'required|string',
            'repeat_every' => 'required|string',
            'repeat_on' => 'required|string',
            'repeat_until' => 'required|date',
            'notes' => 'nullable|string',
            'active' => 'boolean',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }
}
