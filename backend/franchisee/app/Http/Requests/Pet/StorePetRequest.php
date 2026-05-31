<?php

namespace App\Http\Requests\Pet;

use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'breed' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'weight' => 'nullable|numeric',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,unknown',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
        ];
    }
}
