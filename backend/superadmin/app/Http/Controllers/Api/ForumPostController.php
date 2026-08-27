<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use Illuminate\Http\Request;

class ForumPostController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumPost::query()->with(['category:id,name', 'topic:id,name']);

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($topicId = $request->get('topic_id')) {
            $query->where('topic_id', $topicId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->get('per_page', 15);

        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:forum_categories,id',
            'topic_id' => 'nullable|exists:forum_topics,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
        ]);

        $validated['status'] = 'active';
        $validated['views'] = 0;
        $validated['author_name'] = $validated['author_name'] ?? $request->user()->name;

        $post = ForumPost::create($validated);

        return response()->json($post->load(['category:id,name', 'topic:id,name']), 201);
    }

    public function show(ForumPost $forumPost)
    {
        $forumPost->increment('views');

        return response()->json($forumPost->load(['category:id,name', 'topic:id,name']));
    }

    public function update(Request $request, ForumPost $forumPost)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:forum_categories,id',
            'topic_id' => 'nullable|exists:forum_topics,id',
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string',
            'author_name' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $forumPost->update($validated);

        return response()->json($forumPost->load(['category:id,name', 'topic:id,name']));
    }

    public function destroy(ForumPost $forumPost)
    {
        $forumPost->delete();

        return response()->json(['message' => 'Forum post deleted successfully']);
    }
}
