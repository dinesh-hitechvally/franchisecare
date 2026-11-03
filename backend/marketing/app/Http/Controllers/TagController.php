<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index()
    {
        return Tag::withCount('people')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        return Tag::create($this->validated($request));
    }

    public function update(Request $request, Tag $tag)
    {
        $tag->update($this->validated($request, $tag));

        return $tag;
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->noContent();
    }

    protected function validated(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('tags')->where('company_id', $request->user()->company_id)->whereNull('deleted_at')->ignore($tag?->id)],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
