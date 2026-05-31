<?php

namespace App\Http\Requests\ExpenseCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'gst_inclusive' => 'boolean',
            'is_active'     => 'boolean',
        ];
    }
}
