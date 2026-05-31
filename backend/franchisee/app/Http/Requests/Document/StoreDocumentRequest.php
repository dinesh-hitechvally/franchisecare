<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'required|in:global,franchise',
            'category' => 'required|in:manual,template,other,general',
            'file' => 'nullable|file|max:10240',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Handle metadata JSON from form data
        $metadata = json_decode((string) $this->input('metadata', '{}'), true);
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $this->merge([
            'title' => $this->input('title', $metadata['title'] ?? null),
            'description' => $this->input('description', $metadata['description'] ?? null),
            'visibility' => $this->input('visibility', $metadata['visibility'] ?? 'global'),
            'category' => $this->input('category', $metadata['category'] ?? 'other'),
        ]);
    }
}
