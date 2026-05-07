<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumGroup;
use Illuminate\Http\Request;

class ForumGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumGroup::with(['members', 'creator'])
            ->withCount(['members', 'threads']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Get user's groups if filtering by membership
        if ($request->boolean('my_groups')) {
            $query->whereHas('members', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->orderBy('type')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:topic,state,custom',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $group = ForumGroup::create($validated);

        // Automatically add creator as admin member
        $group->members()->attach(auth()->id(), ['role' => 'admin']);

        return response()->json($group->load(['members', 'creator']), 201);
    }

    public function show(ForumGroup $forumGroup)
    {
        return response()->json($forumGroup->load(['members', 'creator', 'threads.author']));
    }

    public function join(ForumGroup $forumGroup)
    {
        if ($forumGroup->isMember(auth()->id())) {
            return response()->json(['message' => 'Already a member'], 400);
        }

        $forumGroup->members()->attach(auth()->id(), ['role' => 'member']);

        return response()->json(['message' => 'Joined successfully']);
    }

    public function leave(ForumGroup $forumGroup)
    {
        if (!$forumGroup->isMember(auth()->id())) {
            return response()->json(['message' => 'Not a member'], 400);
        }

        $forumGroup->members()->detach(auth()->id());

        return response()->json(['message' => 'Left successfully']);
    }

    public function members(ForumGroup $forumGroup)
    {
        return response()->json($forumGroup->members()->get());
    }

    public function update(Request $request, ForumGroup $forumGroup)
    {
        // Only creator or admin can update
        if ($forumGroup->created_by !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $forumGroup->update($validated);

        return response()->json($forumGroup->load(['members', 'creator']));
    }

    public function destroy(ForumGroup $forumGroup)
    {
        // Only creator can delete
        if ($forumGroup->created_by !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $forumGroup->delete();

        return response()->json(null, 204);
    }
}
