<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SaveReminderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reminder_method' => 'in:NO-SEND,EMAIL-SMS,EMAIL-ONLY,SMS-ONLY,EMAIL-IF-NO-MOBILE,SMS-IF-NO-EMAIL',
            'send_before_hours' => 'integer',
        ];
    }
}
