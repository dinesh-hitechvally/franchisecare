<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\UserProfileServiceInterface;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function __construct(
        protected UserProfileServiceInterface $userProfileService
    ) {}

    public function show(User $user): JsonResponse
    {
        return response()->json($this->userProfileService->show($user));
    }

    public function userPosts(User $user, Request $request): JsonResponse
    {
        $filters = ['per_page' => $request->input('per_page', 10)];

        return response()->json($this->userProfileService->userPosts($user, $request->user(), $filters));
    }

    public function createPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
            'group_id' => 'nullable|exists:forum_groups,id',
        ]);

        return response()->json($this->userProfileService->createPost($request->user(), $validated), 201);
    }

    public function updatePost(Request $request, ForumThread $thread): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
        ]);

        $result = $this->userProfileService->updatePost($request->user(), $thread, $validated);

        if (!$result['success']) {
            return response()->json(['message' => $result['error']], $result['status_code']);
        }

        return response()->json($result['data']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:50',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $avatar = $request->hasFile('avatar') ? $request->file('avatar') : null;

        return response()->json($this->userProfileService->updateProfile($request->user(), $validated, $avatar));
    }
}
