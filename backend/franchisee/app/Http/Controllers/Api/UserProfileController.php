<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    public function show(User $user)
    {
        $user->load('company');

        $territoryParts = [
            $user->company?->address,
            $user->company?->city,
            $user->company?->state,
            $user->company?->zip,
        ];

        $territory = collect($territoryParts)
            ->filter(fn ($part) => filled($part))
            ->implode(', ');

        $location = collect([$user->company?->city, $user->company?->state])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');

        return response()->json([
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'company_id' => $user->company_id,
            'company_name' => $user->company?->name,
            'phone' => $user->phone ?? $user->company?->phone,
            'location' => $location,
            'territory' => $territory,
        ]);
    }

    public function userPosts(User $user, Request $request)
    {
        $perPage = $request->input('per_page', 10);
        
        $posts = ForumThread::where('author_id', $user->id)
            ->with(['author', 'group', 'comments' => function ($q) {
                $q->whereNull('parent_id')->with(['author', 'replies.author']);
            }])
            ->latest()
            ->paginate($perPage);

        // Attach liked state for authenticated user
        if (Auth::check()) {
            $this->attachLikedStateToThreads($posts->getCollection(), Auth::id());
        }

        return $posts;
    }

    public function createPost(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
            'group_id' => 'nullable|exists:forum_groups,id',
        ]);

        $validated['author_id'] = Auth::id();
        $thread = ForumThread::create($validated);

        $this->createForumNotifications(
            $thread,
            null,
            'post',
            sprintf('%s created a new post', Auth::user()?->name ?? 'Someone')
        );

        return response()->json($thread->load(['author', 'group']), 201);
    }

    public function updatePost(Request $request, ForumThread $thread)
    {
        if ($thread->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'required|string',
            'topic' => 'nullable|string',
        ]);

        $thread->update($validated);

        return response()->json($thread->load(['author', 'group']), 200);
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|email|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:50',
            'address1'   => 'nullable|string|max:255',
            'address2'   => 'nullable|string|max:255',
            'suburb'     => 'nullable|string|max:255',
            'avatar'     => 'nullable|image|max:2048',
        ]);

        // Build the name from first/last name if provided
        $firstName = $validated['first_name'] ?? null;
        $lastName  = $validated['last_name'] ?? null;
        if ($firstName !== null || $lastName !== null) {
            $user->name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        // Store extra fields in JSON column or individual columns if they exist
        foreach (['phone', 'address1', 'address2', 'suburb', 'first_name', 'last_name'] as $field) {
            if (array_key_exists($field, $validated) && $user->isFillable($field)) {
                $user->$field = $validated[$field];
            }
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Remove old avatar
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id'         => (string) $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'avatar'     => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role'       => $user->role,
                'phone'      => $user->phone ?? null,
                'address1'   => $user->address1 ?? null,
                'address2'   => $user->address2 ?? null,
                'suburb'     => $user->suburb ?? null,
                'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? null,
                'last_name'  => $user->last_name ?? (explode(' ', $user->name)[1] ?? null),
            ],
        ]);
    }

    private function attachLikedStateToThreads($threads, $userId)
    {
        $threadIds = $threads->pluck('id');
        $likedThreadIds = DB::table('forum_thread_likes')
            ->whereIn('forum_thread_id', $threadIds)
            ->where('user_id', $userId)
            ->pluck('forum_thread_id')
            ->toArray();

        $likedCommentIds = DB::table('forum_comment_likes')
            ->whereIn('forum_comment_id', $threads->flatMap(fn ($t) => $t->comments->pluck('id')))
            ->where('user_id', $userId)
            ->pluck('forum_comment_id')
            ->toArray();

        foreach ($threads as $thread) {
            $thread->liked = in_array($thread->id, $likedThreadIds);
            foreach ($thread->comments as $comment) {
                $comment->liked = in_array($comment->id, $likedCommentIds);
                foreach ($comment->replies as $reply) {
                    $reply->liked = in_array($reply->id, $likedCommentIds);
                }
            }
        }
    }

    private function createForumNotifications($thread, $comment, $type, $message)
    {
        $groupId = $thread->group_id;

        if ($groupId) {
            $userIds = DB::table('forum_group_members')
                ->where('forum_group_id', $groupId)
                ->where('user_id', '!=', Auth::id())
                ->pluck('user_id');
        } else {
            $userIds = User::where('id', '!=', Auth::id())->pluck('id');
        }

        foreach ($userIds as $userId) {
            \App\Models\ForumNotification::create([
                'user_id' => $userId,
                'actor_id' => Auth::id(),
                'group_id' => $groupId,
                'thread_id' => $thread->id,
                'comment_id' => $comment?->id,
                'type' => $type,
                'message' => $message,
            ]);
        }
    }
}
