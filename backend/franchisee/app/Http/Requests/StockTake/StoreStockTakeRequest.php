<?php

namespace App\Http\Requests\StockTake;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|integer',
            'values' => 'required|array',
        ];
    }
}
