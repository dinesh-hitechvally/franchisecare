<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:inventory_categories,id',
            'sku' => 'nullable|string|max:100',
            'quantity' => 'sometimes|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'booking_usage' => 'boolean',
        ];
    }

    public function inventoryData(): array
    {
        return $this->validated();
    }
}
