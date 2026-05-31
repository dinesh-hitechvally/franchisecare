<?php

namespace App\Http\Requests\CompanyService;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllCompanyServicesRequest extends FormRequest
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
            'services.*.service_id' => 'required|integer',
            'services.*.my_price' => 'required|numeric|min:0',
            'services.*.my_time' => 'required|integer|min:0',
        ];
    }
}
