<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\Attachments;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        return Template::when($request->query('type'), fn ($q, $t) => $q->where('type', $t))->latest()->get();
    }

    public function store(Request $request)
    {
        return Template::create($this->validated($request));
    }

    public function update(Request $request, Template $template)
    {
        $template->update($this->validated($request));

        return $template;
    }

    public function destroy(Template $template)
    {
        $template->delete();

        return response()->noContent();
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms',
            'subject' => 'nullable|string|max:255|required_if:type,email',
            'body' => 'required|string',
            'design' => 'nullable|array',
            'attachments' => 'nullable|array|max:10',
            'attachments.*.token' => 'required|string|size:32',
        ]);
        $data['attachments'] = $data['type'] === 'email' ? Attachments::clean($data['attachments'] ?? null, $request->user()->company_id) : null;

        return $data;
    }
}
