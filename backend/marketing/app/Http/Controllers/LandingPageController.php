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
        $slug = $this->slugFor($data['slug'] ?? null, $data['name']);
        unset($data['slug']);

        $page = (new LandingPage)->forceFill($data + ['slug' => $slug]); // slug is guarded against mass assignment
        $page->save();
        $page->setAttribute('url', url('/p/'.$page->slug));

        return response($page, 201);
    }

    public function update(Request $request, LandingPage $page)
    {
        $data = $this->validated($request);
        $slug = ! empty($data['slug']) ? $this->slugFor($data['slug'], $data['name'], $page) : null; // empty = keep the address the page already has
        unset($data['slug']);

        $page->update($data);
        if ($slug && $slug !== $page->slug) {
            $page->forceFill(['slug' => $slug])->save();
        }
        $page->setAttribute('url', url('/p/'.$page->slug));

        return $page;
    }

    /** A copy to start from: same design, its own address, not published, no views. */
    public function duplicate(LandingPage $page)
    {
        $name = mb_substr('Copy of '.$page->name, 0, 255);
        $copy = $page->replicate();
        $copy->name = $name;
        $copy->forceFill(['slug' => $this->slugFor(null, $name), 'published' => false, 'views' => 0])->save();
        $copy->setAttribute('url', url('/p/'.$copy->slug));

        return response($copy, 201);
    }

    public function destroy(LandingPage $page)
    {
        $page->delete();

        return response()->noContent();
    }

    /**
     * The address part of /p/{slug}. A typed one is kept (taken = 422); an empty one is made from the page name,
     * with a short random ending when that is taken. Addresses are unique across all companies, deleted pages included.
     */
    protected function slugFor(?string $typed, string $name, ?LandingPage $page = null): string
    {
        $taken = fn (string $s) => LandingPage::withoutGlobalScope('company')->withTrashed()->where('slug', $s)->when($page, fn ($q) => $q->whereKeyNot($page->id))->exists();

        if ($typed !== null && $typed !== '') {
            $slug = Str::slug($typed);
            abort_if($slug === '', 422, 'The address can only use letters, numbers and dashes.');
            abort_if($taken($slug), 422, 'That address is already used by another page. Choose a different one.');

            return $slug;
        }

        $base = Str::limit(Str::slug($name), 30, '') ?: 'page';
        $slug = $base;
        while ($taken($slug)) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/'],
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
