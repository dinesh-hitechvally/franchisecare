<?php

namespace App\Services;

use App\Contracts\Services\ForumServiceInterface;
use App\Models\ForumComment;
use App\Models\ForumNotification;
use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForumService implements ForumServiceInterface
{
    public function index(?Authenticatable $user, array $filters): array
    {
        $query = ForumThread::with(['author', 'group', 'comments' => function ($q) {
            $q->whereNull('parent_id')->with(['author', 'replies.author']);
        }]);

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['no_group'])) {
            $query->whereNull('group_id');
        }

        if (!empty($filters['topic'])) {
            $query->where('topic', $filters['topic']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('content', 'like', $term);
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        $threads = $query->latest()->paginate($perPage);

        $this->attachLikedStateToThreads($threads->getCollection(), $user?->id);

        return $threads->toArray();
    }

    public function store(Authenticatable $user, array $data): ForumThread
    {
        $data['author_id'] = $user->id;
        $thread = ForumThread::create($data);

        $this->createForumNotifications(
            $thread,
            null,
            'post',
            sprintf('%s created a new post', $user->name ?? 'Someone')
        );

        return $thread->load(['author', 'group']);
    }

    public function show(?Authenticatable $user, ForumThread $forumThread): ForumThread
    {
        $thread = $forumThread->load(['author', 'comments' => function ($q) {
            $q->whereNull('parent_id')->with(['author', 'replies.author']);
        }]);

        $this->attachLikedStateToThreads(collect([$thread]), $user?->id);

        return $thread;
    }

    public function destroy(Authenticatable $user, ForumThread $forumThread): array
    {
        if ($forumThread->author_id !== $user->id) {
            return ['success' => false, 'error' => 'Unauthorized', 'status_code' => 403];
        }

        $forumThread->delete();

        return ['success' => true];
    }

    public function addComment(Authenticatable $user, ForumThread $forumThread, string $content): ForumComment
    {
        $comment = $forumThread->comments()->create([
            'author_id' => $user->id,
            'content' => $content,
        ]);

        $this->createForumNotifications(
            $forumThread,
            $comment,
            'comment',
            sprintf('%s commented on a post', $user->name ?? 'Someone')
        );

        return $comment->load('author');
    }

    public function like(Authenticatable $user, ForumThread $forumThread): array
    {
        $userId = $user->id;
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
                sprintf('%s liked a post', $user->name ?? 'Someone')
            );
        }

        return [
            'likes_count' => $likesCount,
            'liked' => !$alreadyLiked,
        ];
    }

    public function likeComment(Authenticatable $user, ForumComment $forumComment): array
    {
        $userId = $user->id;
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
                sprintf('%s liked a comment', $user->name ?? 'Someone')
            );
        }

        return [
            'likes_count' => $likesCount,
            'liked' => !$alreadyLiked,
        ];
    }

    public function replyToComment(Authenticatable $user, ForumComment $forumComment, string $content): ForumComment
    {
        $reply = ForumComment::create([
            'thread_id' => $forumComment->thread_id,
            'parent_id' => $forumComment->id,
            'author_id' => $user->id,
            'content' => $content,
        ]);

        $thread = $forumComment->thread()->first();
        if ($thread) {
            $this->createForumNotifications(
                $thread,
                $reply,
                'reply',
                sprintf('%s replied to a comment', $user->name ?? 'Someone')
            );
        }

        return $reply->load('author');
    }

    public function notifications(Authenticatable $user, array $filters): array
    {
        $query = ForumNotification::with(['actor:id,name,avatar'])
            ->where('user_id', $user->id);

        if (!empty($filters['unread_only'])) {
            $query->where('is_read', false);
        }

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['no_group'])) {
            $query->whereNull('group_id');
        }

        $limit = (int) ($filters['limit'] ?? 50);
        $limit = max(1, min($limit, 200));

        return $query->latest()->limit($limit)->get()->toArray();
    }

    public function markAllNotificationsAsRead(Authenticatable $user, array $filters): array
    {
        $query = ForumNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false);

        if (!empty($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (!empty($filters['no_group'])) {
            $query->whereNull('group_id');
        }

        $updated = $query->update(['is_read' => true]);

        return ['updated' => $updated];
    }

    public function markNotificationAsRead(Authenticatable $user, ForumNotification $forumNotification): array
    {
        if ((int) $forumNotification->user_id !== (int) $user->id) {
            return ['success' => false, 'error' => 'Unauthorized', 'status_code' => 403];
        }

        $forumNotification->update(['is_read' => true]);

        return ['success' => true, 'data' => $forumNotification->toArray()];
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

    private function createForumNotifications(
        ForumThread $thread,
        ?ForumComment $comment,
        string $type,
        string $message
    ): void {
        $actorId = auth()->id();
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
