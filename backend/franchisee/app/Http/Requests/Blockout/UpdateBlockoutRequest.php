<?php

namespace App\Http\Requests\Blockout;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalise camelCase keys to snake_case
        $keyMap = [
            'startDate' => 'start_date',
            'startTime' => 'start_time',
            'endDate' => 'end_date',
            'endTime' => 'end_time',
            'isRecurring' => 'is_recurring',
            'recurringId' => 'recurring_id',
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
            'is_recurring' => 'sometimes|boolean',
            'recurring_id' => 'nullable|exists:blockout_recurrings,id',
            'repeat_every' => 'nullable|string',
            'repeat_on' => 'nullable|string',
            'repeat_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ];
    }

    public function blockoutData(): array
    {
        return $this->validated();
    }
}
