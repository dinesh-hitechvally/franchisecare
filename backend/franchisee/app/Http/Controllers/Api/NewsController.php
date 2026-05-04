<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = News::with('author');

        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published);
        }

        if ($request->filled('category') && $request->category !== 'All News') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('content', 'like', $term);
            });
        }

        $perPage = $request->input('per_page', 10);
        return $query->latest()->paginate($perPage);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $validated['author_id'] = auth()->id();
        if ($request->input('is_published')) {
            $validated['published_at'] = now();
        }

        $news = News::create($validated);

        return response()->json($news, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        return response()->json($news->load('author'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'sometimes|string',
            'is_published' => 'sometimes|boolean',
        ]);

        if ($request->has('is_published') && $request->is_published && !$news->is_published) {
            $validated['published_at'] = now();
        }

        $news->update($validated);

        return response()->json($news);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(null, 204);
    }

    /**
     * Publish the specified news.
     */
    public function publish(News $news)
    {
        $news->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return response()->json($news);
    }
}
