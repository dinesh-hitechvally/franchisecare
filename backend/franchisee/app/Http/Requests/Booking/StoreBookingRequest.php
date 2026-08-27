<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Single Responsibility Principle (SRP):
 * This class handles ONLY validation for creating a booking.
 * Validation logic is separated from the controller.
 */
class StoreBookingRequest extends FormRequest
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
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'required|in:ACTIVE,CANCELLED,COMPLETED,ARCHIVED',
            'total' => 'required|numeric',
            'duration' => 'required|integer',
            'notes' => 'nullable|string',
            'ndis_notes' => 'nullable|string',
            'services' => 'required|array',
            'services.*.item_id' => 'required|exists:customer_items,id',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'start_date.required' => 'Please select a booking date.',
            'start_time.required' => 'Please select a start time.',
            'services.required' => 'At least one service is required.',
            'services.*.item_id.required' => 'Each service must have a pet selected.',
            'services.*.service_id.required' => 'Each service must have a service type selected.',
        ];
    }

    /**
     * Get the validated data for the booking (excluding services).
     */
    public function bookingData(): array
    {
        return $this->safe()->except(['services']);
    }

    /**
     * Get the services data.
     */
    public function servicesData(): array
    {
        return $this->validated()['services'];
    }
}
