<?php

namespace App\Http\Requests\Income;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_category_id' => 'nullable|exists:income_categories,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'income_date' => 'required|date',
            'is_active' => 'boolean',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'auto_extend_recurring' => 'boolean',
        ];
    }

    public function incomeData(): array
    {
        return $this->validated();
    }
}
