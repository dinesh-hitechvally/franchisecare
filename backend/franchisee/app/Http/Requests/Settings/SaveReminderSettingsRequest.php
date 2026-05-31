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
            'reminder_method' => 'in:no-send,email-sms,email-only,sms-only,email-if-found,sms-if-no-mobile',
            'send_before_hours' => 'integer',
        ];
    }
}
