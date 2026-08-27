<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use Illuminate\Http\Request;

class ForumTopicController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumTopic::query()->with('category:id,name')->withCount('posts');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('category_id', $categoryId);
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = 'ACTIVE';

        $topic = ForumTopic::create($validated);

        return response()->json($topic->load('category:id,name'), 201);
    }

    public function show(ForumTopic $forumTopic)
    {
        return response()->json($forumTopic->load('category:id,name')->loadCount('posts'));
    }

    public function update(Request $request, ForumTopic $forumTopic)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:forum_categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $forumTopic->update($validated);

        return response()->json($forumTopic->load('category:id,name')->loadCount('posts'));
    }

    public function destroy(ForumTopic $forumTopic)
    {
        $forumTopic->delete();

        return response()->json(['message' => 'Forum topic deleted successfully']);
    }
}
