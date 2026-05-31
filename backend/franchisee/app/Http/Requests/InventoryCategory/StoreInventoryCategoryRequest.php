<?php

namespace App\Http\Requests\InventoryCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ];
    }
}
