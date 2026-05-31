<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id ?? $this->route('customer');

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $customerId,
            'other_email' => 'nullable|email',
            'phone' => 'sometimes|required|string',
            'other_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'street_address' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
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
