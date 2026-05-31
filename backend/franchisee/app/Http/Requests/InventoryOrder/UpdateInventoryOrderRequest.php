<?php

namespace App\Http\Requests\InventoryOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:pending,confirmed,shipped,delivered,cancelled',
            'notes' => 'nullable|string',
        ];
    }
}
