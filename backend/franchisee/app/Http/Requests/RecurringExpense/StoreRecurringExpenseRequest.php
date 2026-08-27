<?php

namespace App\Http\Requests\RecurringExpense;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'start_date'          => 'required|date',
            'frequency'           => 'required|in:DAILY,WEEKLY,MONTHLY,YEARLY',
            'auto_extend'         => 'boolean',
            'total'               => 'required|numeric|min:0',
            'notes'               => 'nullable|string',
        ];
    }
}
