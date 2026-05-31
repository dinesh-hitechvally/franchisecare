<?php

namespace App\Http\Requests\ServiceInventoryUsage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceInventoryUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'sometimes|exists:services,id',
            'inventory_id' => 'sometimes|exists:inventory_items,id',
            'quantity_per_booking' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
