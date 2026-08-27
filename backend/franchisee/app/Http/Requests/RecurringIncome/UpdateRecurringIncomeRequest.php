<?php

namespace App\Http\Requests\RecurringIncome;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_category_id' => 'nullable|exists:income_categories,id',
            'start_date'         => 'sometimes|required|date',
            'frequency'          => 'sometimes|required|in:DAILY,WEEKLY,MONTHLY,YEARLY',
            'auto_extend'        => 'boolean',
            'total'              => 'sometimes|required|numeric|min:0',
            'notes'              => 'nullable|string',
            'status'             => 'sometimes|in:ACTIVE,CANCELLED,COMPLETED',
        ];
    }
}
