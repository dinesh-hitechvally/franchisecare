<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SaveIncomeTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_title_template' => 'nullable|string',
            'invoice_statement_template' => 'nullable|string',
        ];
    }
}
