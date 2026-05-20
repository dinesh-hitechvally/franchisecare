<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::with('author:id,name');

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->get('category')) {
            $query->where('category', $category);
        }

        $perPage = $request->get('per_page', 15);
        
        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'status' => 'required|in:draft,published',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('news', 'public');
        }

        $validated['user_id'] = auth()->id();
        
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $news = News::create($validated);

        return response()->json($news->load('author:id,name'), 201);
        
    }

    public function show(News $news)
    {
        return response()->json($news->load('author:id,name'));
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'nullable|string|max:50',
            'status' => 'sometimes|in:draft,published',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }
            $validated['image'] = $request->file('image')->store('news', 'public');
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && !$news->published_at) {
            $validated['published_at'] = now();
        }

        $news->update($validated);

        return response()->json($news->load('author:id,name'));
    }

    public function destroy(News $news)
    {
        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }
        
        $news->delete();

        return response()->json(['message' => 'News deleted successfully']);
    }

    public function publish(News $news)
    {
        $news->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json($news);
    }
}
