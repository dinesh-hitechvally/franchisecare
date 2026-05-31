<?php

namespace App\Http\Requests\IntakeForm;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntakeFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'item_id' => 'required|exists:customer_items,id',
            'waiver_type' => 'required|string',
            'ownerName' => 'required|string',
            'petName' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|string',
            'breed' => 'nullable|string',
            'form_data' => 'nullable|array',
            'signature' => 'required|string',
        ];
    }
}
