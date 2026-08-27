<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'is_active' => 'boolean',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:DAILY,WEEKLY,MONTHLY,YEARLY',
            'auto_extend_recurring' => 'boolean',
        ];
    }

    public function expenseData(): array
    {
        return $this->validated();
    }
}
