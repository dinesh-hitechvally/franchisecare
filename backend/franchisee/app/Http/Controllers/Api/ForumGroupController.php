<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ForumGroupServiceInterface;
use App\Models\ForumGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumGroupController extends Controller
{
    public function __construct(
        protected ForumGroupServiceInterface $forumGroupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'my_groups']);
        $filters['my_groups'] = $request->boolean('my_groups');

        return response()->json($this->forumGroupService->index($request->user(), $filters));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:TOPIC,STATE,CUSTOM',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        return response()->json($this->forumGroupService->store($request->user(), $validated), 201);
    }

    public function show(ForumGroup $forumGroup): JsonResponse
    {
        return response()->json($this->forumGroupService->show($forumGroup));
    }

    public function update(Request $request, ForumGroup $forumGroup): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $result = $this->forumGroupService->update($request->user(), $forumGroup, $validated);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json($result['data']);
    }

    public function destroy(Request $request, ForumGroup $forumGroup): JsonResponse
    {
        $result = $this->forumGroupService->destroy($request->user(), $forumGroup);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json(null, 204);
    }

    public function join(Request $request, ForumGroup $forumGroup): JsonResponse
    {
        $result = $this->forumGroupService->join($request->user(), $forumGroup);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function leave(Request $request, ForumGroup $forumGroup): JsonResponse
    {
        $result = $this->forumGroupService->leave($request->user(), $forumGroup);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function members(ForumGroup $forumGroup): JsonResponse
    {
        return response()->json($this->forumGroupService->members($forumGroup));
    }
}
