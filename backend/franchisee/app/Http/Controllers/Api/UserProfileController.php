<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
