<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Single Responsibility Principle (SRP):
 * This class handles ONLY validation for updating a booking.
 */
class UpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Normalizes camelCase keys to snake_case.
     */
    protected function prepareForValidation(): void
    {
        $keyMap = [
            'startDate'     => 'start_date',
            'startTime'     => 'start_time',
            'endTime'       => 'end_time',
            'endDate'       => 'end_date',
            'calendarColor' => 'calendar_color',
            'customerId'    => 'customer_id',
            'sendSms'       => 'send_sms',
            'sendEmail'     => 'send_email',
        ];

        $input = $this->all();
        foreach ($keyMap as $camel => $snake) {
            if (array_key_exists($camel, $input) && !array_key_exists($snake, $input)) {
                $input[$snake] = $input[$camel];
                unset($input[$camel]);
            }
        }

        $this->replace($input);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_time' => 'nullable|string',
            'end_date' => 'nullable|date',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'sometimes|in:active,cancelled,completed,archived',
            'total' => 'sometimes|numeric',
            'duration' => 'sometimes|integer',
            'notes' => 'nullable|string',
            'ndis_notes' => 'nullable|string',
            'services' => 'sometimes|array',
            'services.*.item_id' => 'required_with:services|exists:customer_items,id',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
        ];
    }

    /**
     * Get the validated data for the booking (excluding services).
     */
    public function bookingData(): array
    {
        return $this->safe()->except(['services'])->toArray();
    }

    /**
     * Get the services data if present.
     */
    public function servicesData(): ?array
    {
        return $this->validated()['services'] ?? null;
    }
}
