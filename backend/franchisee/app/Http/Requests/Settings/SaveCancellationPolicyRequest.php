<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SaveCancellationPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attach_policy' => 'boolean',
            'cancel_before_unit' => 'in:HOURS,CUTOFF',
            'cancel_before_value' => 'integer',
            'cancel_cutoff_time' => 'nullable|date_format:H:i',
            'cancellation_fee_value' => 'numeric',
            'penalty_type' => 'in:PERCENT,FIXED',
            'policy_id' => 'nullable|integer',
            'policy_text' => 'nullable|string',
        ];
    }
}
