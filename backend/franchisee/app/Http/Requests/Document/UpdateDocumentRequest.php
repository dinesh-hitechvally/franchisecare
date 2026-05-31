<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|in:global,franchise',
            'category' => 'nullable|in:manual,template,other,general',
            'file' => 'nullable|file|max:10240',
        ];
    }
}
