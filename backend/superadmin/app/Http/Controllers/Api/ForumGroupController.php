<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumGroup;
use Illuminate\Http\Request;

class ForumGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumGroup::query();

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
            'permissions' => 'nullable|string|max:255',
        ]);

        $validated['status'] = 'ACTIVE';

        $group = ForumGroup::create($validated);

        return response()->json($group, 201);
    }

    public function show(ForumGroup $forumGroup)
    {
        return response()->json($forumGroup);
    }

    public function update(Request $request, ForumGroup $forumGroup)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'nullable|string|max:255',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $forumGroup->update($validated);

        return response()->json($forumGroup);
    }

    public function destroy(ForumGroup $forumGroup)
    {
        $forumGroup->delete();

        return response()->json(['message' => 'Forum group deleted successfully']);
    }
}
