<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PayBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transient_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'booking_id' => 'required|exists:bookings,id',
            'customer_id' => 'nullable|exists:customers,id',
            'currency' => 'nullable|string|size:3',
        ];
    }
}
