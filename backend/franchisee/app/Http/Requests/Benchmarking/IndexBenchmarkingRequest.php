<?php

namespace App\Http\Requests\Benchmarking;

use Illuminate\Foundation\Http\FormRequest;

class IndexBenchmarkingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ];
    }
}
