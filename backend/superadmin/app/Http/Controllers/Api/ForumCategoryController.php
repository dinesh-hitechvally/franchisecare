<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use Illuminate\Http\Request;

class ForumCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumCategory::query()->withCount('posts');

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = 'ACTIVE';

        $category = ForumCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show(ForumCategory $forumCategory)
    {
        return response()->json($forumCategory->loadCount('posts'));
    }

    public function update(Request $request, ForumCategory $forumCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $forumCategory->update($validated);

        return response()->json($forumCategory->loadCount('posts'));
    }

    public function destroy(ForumCategory $forumCategory)
    {
        $forumCategory->delete();

        return response()->json(['message' => 'Forum category deleted successfully']);
    }
}
