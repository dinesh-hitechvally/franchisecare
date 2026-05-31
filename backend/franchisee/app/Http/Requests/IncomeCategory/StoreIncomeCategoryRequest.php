<?php

namespace App\Http\Requests\IncomeCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'gst_inclusive' => 'boolean',
            'is_active'     => 'boolean',
        ];
    }
}
