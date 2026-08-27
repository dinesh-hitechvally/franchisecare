<?php

namespace App\Http\Requests\RecurringIncome;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_category_id' => 'nullable|exists:income_categories,id',
            'start_date'         => 'required|date',
            'frequency'          => 'required|in:DAILY,WEEKLY,MONTHLY,YEARLY',
            'auto_extend'        => 'boolean',
            'total'              => 'required|numeric|min:0',
            'notes'              => 'nullable|string',
        ];
    }
}
