<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumNotification;
use App\Models\ForumThread;
use App\Models\ForumComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $query = ForumThread::with(['author', 'group', 'comments' => function ($q) {
            $q->whereNull('parent_id')->with(['author', 'replies.author']);
        }]);

        // Filter by group
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        // Filter posts without group (Daily Chat / Latest Posts)
        if ($request->boolean('no_group')) {
            $query->whereNull('group_id');
        }

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
        $threads = $query->latest()->paginate($perPage);

        $this->attachLikedStateToThreads($threads->getCollection(), Auth::id());

        return $threads;
    }

    public function store(Request $request)
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

    public function show(ForumThread $forumThread)
    {
        $thread = $forumThread->load(['author', 'comments' => function ($q) {
            $q->whereNull('parent_id')->with(['author', 'replies.author']);
        }]);

        $this->attachLikedStateToThreads(collect([$thread]), Auth::id());

        return response()->json($thread);
    }

    public function addComment(Request $request, ForumThread $forumThread)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment = $forumThread->comments()->create([
            'author_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        $this->createForumNotifications(
            $forumThread,
            $comment,
            'comment',
            sprintf('%s commented on a post', Auth::user()?->name ?? 'Someone')
        );

        return response()->json($comment->load('author'), 201);
    }

    public function like(ForumThread $forumThread)
    {
        $userId = Auth::id();
        $alreadyLiked = $forumThread->likedByUsers()->where('user_id', $userId)->exists();

        if ($alreadyLiked) {
            $forumThread->likedByUsers()->detach($userId);
        } else {
            $forumThread->likedByUsers()->attach($userId);
        }

        $likesCount = $forumThread->likedByUsers()->count();
        $forumThread->update(['likes_count' => $likesCount]);

        if (!$alreadyLiked) {
            $this->createForumNotifications(
                $forumThread,
                null,
                'like_thread',
                sprintf('%s liked a post', Auth::user()?->name ?? 'Someone')
            );
        }

        return response()->json([
            'likes_count' => $likesCount,
            'liked' => !$alreadyLiked,
        ]);
    }

    public function destroy(ForumThread $forumThread)
    {
        if ($forumThread->author_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $forumThread->delete();
        return response()->json(null, 204);
    }

    public function likeComment(ForumComment $forumComment)
    {
        $userId = Auth::id();
        $alreadyLiked = $forumComment->likedByUsers()->where('user_id', $userId)->exists();

        if ($alreadyLiked) {
            $forumComment->likedByUsers()->detach($userId);
        } else {
            $forumComment->likedByUsers()->attach($userId);
        }

        $likesCount = $forumComment->likedByUsers()->count();
        $forumComment->update(['likes_count' => $likesCount]);

        if (!$alreadyLiked) {
            $this->createForumNotifications(
                $forumComment->thread,
                $forumComment,
                'like_comment',
                sprintf('%s liked a comment', Auth::user()?->name ?? 'Someone')
            );
        }

        return response()->json([
            'likes_count' => $likesCount,
            'liked' => !$alreadyLiked,
        ]);
    }

    private function attachLikedStateToThreads(Collection $threads, ?int $userId): void
    {
        if ($threads->isEmpty()) {
            return;
        }

        if (!$userId) {
            $threads->each(function ($thread) {
                $thread->setAttribute('liked', false);
                $thread->comments?->each(function ($comment) {
                    $comment->setAttribute('liked', false);
                    $comment->replies?->each(fn ($reply) => $reply->setAttribute('liked', false));
                });
            });

            return;
        }

        $threadIds = $threads->pluck('id')->all();

        $likedThreadIds = DB::table('forum_thread_likes')
            ->where('user_id', $userId)
            ->whereIn('forum_thread_id', $threadIds)
            ->pluck('forum_thread_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $commentIds = [];
        $threads->each(function ($thread) use (&$commentIds) {
            foreach ($thread->comments ?? [] as $comment) {
                $commentIds[] = (int) $comment->id;

                foreach ($comment->replies ?? [] as $reply) {
                    $commentIds[] = (int) $reply->id;
                }
            }
        });

        $likedCommentIds = collect();
        if (!empty($commentIds)) {
            $likedCommentIds = DB::table('forum_comment_likes')
                ->where('user_id', $userId)
                ->whereIn('forum_comment_id', $commentIds)
                ->pluck('forum_comment_id')
                ->map(fn ($id) => (int) $id)
                ->flip();
        }

        $threads->each(function ($thread) use ($likedThreadIds, $likedCommentIds) {
            $thread->setAttribute('liked', $likedThreadIds->has((int) $thread->id));

            $thread->comments?->each(function ($comment) use ($likedCommentIds) {
                $comment->setAttribute('liked', $likedCommentIds->has((int) $comment->id));
                $comment->replies?->each(fn ($reply) => $reply->setAttribute('liked', $likedCommentIds->has((int) $reply->id)));
            });
        });
    }

    public function replyToComment(Request $request, ForumComment $forumComment)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $reply = ForumComment::create([
            'thread_id' => $forumComment->thread_id,
            'parent_id' => $forumComment->id,
            'author_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        $thread = $forumComment->thread()->first();
        if ($thread) {
            $this->createForumNotifications(
                $thread,
                $reply,
                'reply',
                sprintf('%s replied to a comment', Auth::user()?->name ?? 'Someone')
            );
        }

        return response()->json($reply->load('author'), 201);
    }

    public function notifications(Request $request)
    {
        $query = ForumNotification::with(['actor:id,name,avatar'])
            ->where('user_id', Auth::id());

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->boolean('no_group')) {
            $query->whereNull('group_id');
        }

        $limit = (int) $request->input('limit', 50);
        $limit = max(1, min($limit, 200));

        return response()->json($query->latest()->limit($limit)->get());
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        $query = ForumNotification::query()
            ->where('user_id', Auth::id())
            ->where('is_read', false);

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->boolean('no_group')) {
            $query->whereNull('group_id');
        }

        $updated = $query->update(['is_read' => true]);

        return response()->json(['updated' => $updated]);
    }

    public function markNotificationAsRead(ForumNotification $forumNotification)
    {
        if ((int) $forumNotification->user_id !== (int) Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $forumNotification->update(['is_read' => true]);

        return response()->json($forumNotification);
    }

    private function createForumNotifications(
        ForumThread $thread,
        ?ForumComment $comment,
        string $type,
        string $message
    ): void {
        $actorId = Auth::id();
        if (!$actorId) {
            return;
        }

        $recipientIds = $this->getForumNotificationRecipientIds($thread->group_id, $actorId);
        if ($recipientIds->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $recipientIds->map(function ($recipientId) use ($actorId, $thread, $comment, $type, $message, $now) {
            return [
                'user_id' => $recipientId,
                'actor_id' => $actorId,
                'group_id' => $thread->group_id,
                'thread_id' => $thread->id,
                'comment_id' => $comment?->id,
                'type' => $type,
                'message' => $message,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('forum_notifications')->insert($rows);
    }

    private function getForumNotificationRecipientIds(?int $groupId, int $actorId): Collection
    {
        if ($groupId) {
            return DB::table('forum_group_members')
                ->where('group_id', $groupId)
                ->where('user_id', '!=', $actorId)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        }

        return User::query()
            ->where('id', '!=', $actorId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }
}
