<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseSmsCreditRequest extends FormRequest
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
            'credits' => 'required|integer|min:1',
            'currency' => 'nullable|string|size:3',
            'package' => 'nullable|string',
        ];
    }
}
