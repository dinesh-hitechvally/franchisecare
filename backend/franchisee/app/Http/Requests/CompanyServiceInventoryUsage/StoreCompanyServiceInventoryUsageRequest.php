<?php

namespace App\Http\Requests\CompanyServiceInventoryUsage;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyServiceInventoryUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'inventory_id' => 'required|exists:inventory_items,id',
            'quantity_per_booking' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
