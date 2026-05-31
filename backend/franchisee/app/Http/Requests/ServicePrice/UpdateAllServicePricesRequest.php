<?php

namespace App\Http\Requests\ServicePrice;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllServicePricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'services' => 'required|array',
            'services.*.id' => 'nullable|integer',
            'services.*.name' => 'required|string',
            'services.*.my_price' => 'required|numeric|min:0',
            'services.*.default_price' => 'required|numeric|min:0',
            'services.*.color' => 'required|string',
            'services.*.my_time' => 'required|integer|min:0',
            'services.*.default_time' => 'required|integer|min:0',
        ];
    }
}
