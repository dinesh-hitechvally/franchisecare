<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class SendBulkEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'from_name' => 'nullable|string|max:255',
        ];
    }
}
