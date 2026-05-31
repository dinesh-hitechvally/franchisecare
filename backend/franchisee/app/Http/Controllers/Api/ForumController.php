<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ForumServiceInterface;
use App\Models\ForumComment;
use App\Models\ForumNotification;
use App\Models\ForumThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
    public function __construct(
        protected ForumServiceInterface $forumService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['group_id', 'no_group', 'topic', 'search', 'per_page']);
        $filters['no_group'] = $request->boolean('no_group');

        return response()->json($this->forumService->index($request->user(), $filters));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
            'group_id' => 'nullable|exists:forum_groups,id',
        ]);

        return response()->json($this->forumService->store($request->user(), $validated), 201);
    }

    public function show(Request $request, ForumThread $forumThread): JsonResponse
    {
        return response()->json($this->forumService->show($request->user(), $forumThread));
    }

    public function destroy(Request $request, ForumThread $forumThread): JsonResponse
    {
        $result = $this->forumService->destroy($request->user(), $forumThread);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json(null, 204);
    }

    public function addComment(Request $request, ForumThread $forumThread): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        return response()->json($this->forumService->addComment($request->user(), $forumThread, $validated['content']), 201);
    }

    public function like(Request $request, ForumThread $forumThread): JsonResponse
    {
        return response()->json($this->forumService->like($request->user(), $forumThread));
    }

    public function likeComment(Request $request, ForumComment $forumComment): JsonResponse
    {
        return response()->json($this->forumService->likeComment($request->user(), $forumComment));
    }

    public function replyToComment(Request $request, ForumComment $forumComment): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        return response()->json($this->forumService->replyToComment($request->user(), $forumComment, $validated['content']), 201);
    }

    public function notifications(Request $request): JsonResponse
    {
        $filters = $request->only(['unread_only', 'group_id', 'no_group', 'limit']);
        $filters['unread_only'] = $request->boolean('unread_only');
        $filters['no_group'] = $request->boolean('no_group');

        return response()->json($this->forumService->notifications($request->user(), $filters));
    }

    public function markAllNotificationsAsRead(Request $request): JsonResponse
    {
        $filters = $request->only(['group_id', 'no_group']);
        $filters['no_group'] = $request->boolean('no_group');

        return response()->json($this->forumService->markAllNotificationsAsRead($request->user(), $filters));
    }

    public function markNotificationAsRead(Request $request, ForumNotification $forumNotification): JsonResponse
    {
        $result = $this->forumService->markNotificationAsRead($request->user(), $forumNotification);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json($result['data']);
    }
}
