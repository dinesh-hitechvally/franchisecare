<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers',
            'phone' => 'required|string',
            'other_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'street_address' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'other_email' => 'nullable|email',
            'referred_by' => 'nullable|string',
            'is_ndis' => 'nullable|boolean',
            'is_subscribed' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function customerData(): array
    {
        return $this->validated();
    }
}
