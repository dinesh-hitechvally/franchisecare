<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        return LandingPage::with('form:id,name')->latest()->get()->each(fn ($p) => $p->setAttribute('url', url('/p/'.$p->slug)));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        do {
            $slug = Str::lower(Str::random(10));
        } while (LandingPage::withoutGlobalScope('company')->where('slug', $slug)->exists());

        $page = (new LandingPage)->forceFill($data + ['slug' => $slug]); // slug is guarded against mass assignment
        $page->save();
        $page->setAttribute('url', url('/p/'.$page->slug));

        return response($page, 201);
    }

    public function update(Request $request, LandingPage $page)
    {
        $page->update($this->validated($request));
        $page->setAttribute('url', url('/p/'.$page->slug));

        return $page;
    }

    public function destroy(LandingPage $page)
    {
        $page->delete();

        return response()->noContent();
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'design' => 'nullable|array',
            'html' => 'required|string',
            'form_id' => 'nullable|integer',
            'published' => 'boolean',
        ]);
        if (! empty($data['form_id'])) {
            abort_unless(Form::whereKey($data['form_id'])->exists(), 422, 'Invalid form.');
        }

        return $data;
    }
}
