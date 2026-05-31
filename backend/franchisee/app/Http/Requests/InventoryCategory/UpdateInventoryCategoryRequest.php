<?php

namespace App\Http\Requests\InventoryCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ];
    }
}
