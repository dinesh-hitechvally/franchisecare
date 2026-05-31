<?php

namespace App\Http\Requests\BlockoutRecurring;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockoutRecurringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $keyMap = [
            'startDate' => 'start_date',
            'startTime' => 'start_time',
            'endDate' => 'end_date',
            'endTime' => 'end_time',
            'repeatEvery' => 'repeat_every',
            'repeatOn' => 'repeat_on',
            'repeatUntil' => 'repeat_until',
        ];

        $input = $this->all();
        foreach ($keyMap as $camel => $snake) {
            if (array_key_exists($camel, $input) && !array_key_exists($snake, $input)) {
                $input[$snake] = $input[$camel];
                unset($input[$camel]);
            }
        }
        $this->replace($input);
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'location' => 'nullable|string|max:255',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date',
            'end_time' => 'sometimes|string',
            'repeat_every' => 'sometimes|string',
            'repeat_on' => 'sometimes|string',
            'repeat_until' => 'sometimes|date',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ];
    }
}
