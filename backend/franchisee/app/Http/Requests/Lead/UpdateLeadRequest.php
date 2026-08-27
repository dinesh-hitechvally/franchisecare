<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'alternate_phone' => 'nullable|string|max:255',
            'interested_services' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:50',
            'pet_breed' => 'nullable|string|max:255',
            'referred_by' => 'nullable|string|max:255',
            'additional_note' => 'nullable|string',
            'notes' => 'nullable|string',
            'source' => 'nullable|in:PHONE,INTERNET,WALK-IN,REFERRAL',
            'leads_from' => 'nullable|in:PHONE,INTERNET',
            'status' => 'nullable|in:NEW,CONTACTED,QUALIFIED,CONVERTED,LOST,SNOOZED,COMPLETED,CANCELLATION_REQUEST,MESSAGE_FOR_OPERATOR',
            'snoozed_until' => 'nullable|date',
        ];
    }

    public function leadData(): array
    {
        return $this->validated();
    }
}
