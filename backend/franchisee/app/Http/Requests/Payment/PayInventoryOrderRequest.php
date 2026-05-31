<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PayInventoryOrderRequest extends FormRequest
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
            'order_id' => 'required|exists:inventory_orders,id',
            'currency' => 'nullable|string|size:3',
        ];
    }
}
