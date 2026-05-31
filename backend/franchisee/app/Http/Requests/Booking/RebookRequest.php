<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Single Responsibility Principle (SRP):
 * This class handles ONLY validation for rebooking.
 */
class RebookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select a new booking date.',
            'start_time.required' => 'Please select a new start time.',
        ];
    }

    /**
     * Get the validated datetime data.
     */
    public function dateTimeData(): array
    {
        return $this->validated();
    }
}
