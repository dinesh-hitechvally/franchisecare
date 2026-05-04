<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumThread;
use App\Models\ForumComment;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumThread::with(['author', 'comments.author']);

        if ($request->filled('topic')) {
            $query->where('topic', $request->topic);
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
        ]);

        $validated['author_id'] = auth()->id();
        $thread = ForumThread::create($validated);

        return response()->json($thread->load('author'), 201);
    }

    public function show(ForumThread $forumThread)
    {
        return response()->json($forumThread->load(['author', 'comments.author']));
    }

    public function addComment(Request $request, ForumThread $forumThread)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $forumThread->comments()->create([
            'author_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return response()->json($comment->load('author'), 201);
    }

    public function like(ForumThread $forumThread)
    {
        $forumThread->increment('likes_count');
        return response()->json(['likes_count' => $forumThread->likes_count]);
    }

    public function destroy(ForumThread $forumThread)
    {
        if ($forumThread->author_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $forumThread->delete();
        return response()->json(null, 204);
    }
}
