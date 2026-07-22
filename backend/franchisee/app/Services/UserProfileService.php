<?php

namespace App\Services;

use App\Contracts\Services\UserProfileServiceInterface;
use App\Models\ForumNotification;
use App\Models\ForumThread;
use App\Models\User;
use App\Models\Attachment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserProfileService implements UserProfileServiceInterface
{
    public function show(User $user): array
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

        return [
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
        ];
    }

    public function userPosts(User $user, ?Authenticatable $currentUser, array $filters): array
    {
        $perPage = $filters['per_page'] ?? 10;

        $posts = ForumThread::where('author_id', $user->id)
            ->with(['author', 'group', 'comments' => function ($q) {
                $q->whereNull('parent_id')->with(['author', 'replies.author']);
            }])
            ->latest()
            ->paginate($perPage);

        if ($currentUser) {
            $this->attachLikedStateToThreads($posts->getCollection(), $currentUser->id);
        }

        return $posts->toArray();
    }

    public function createPost(Authenticatable $user, array $data): ForumThread
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

    public function updatePost(Authenticatable $user, ForumThread $thread, array $data): array
    {
        if ($thread->author_id !== $user->id) {
            return ['success' => false, 'error' => 'Unauthorized', 'status_code' => 403];
        }

        $thread->update($data);

        return ['success' => true, 'data' => $thread->load(['author', 'group'])];
    }

    public function updateProfile(Authenticatable $user, array $data, ?UploadedFile $avatar): array
    {
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;

        if ($firstName !== null || $lastName !== null) {
            $user->name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        foreach (['phone', 'address1', 'address2', 'suburb', 'first_name', 'last_name'] as $field) {
            if (array_key_exists($field, $data) && $user->isFillable($field)) {
                $user->$field = $data[$field];
            }
        }

        if ($avatar) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
                Attachment::where('file_path', $user->avatar)->delete();
            }
            $attachment = AttachmentService::upload($avatar, 'avatars');
            $user->avatar = $attachment->file_path;
        }

        $user->save();

        return [
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                'role' => $user->role,
                'phone' => $user->phone ?? null,
                'address1' => $user->address1 ?? null,
                'address2' => $user->address2 ?? null,
                'suburb' => $user->suburb ?? null,
                'first_name' => $user->first_name ?? explode(' ', $user->name)[0] ?? null,
                'last_name' => $user->last_name ?? (explode(' ', $user->name)[1] ?? null),
            ],
        ];
    }

    private function attachLikedStateToThreads($threads, $userId): void
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

    private function createForumNotifications($thread, $comment, $type, $message): void
    {
        $groupId = $thread->group_id;
        $actorId = auth()->id();

        if (!$actorId) {
            return;
        }

        if ($groupId) {
            $userIds = DB::table('forum_group_members')
                ->where('forum_group_id', $groupId)
                ->where('user_id', '!=', $actorId)
                ->pluck('user_id');
        } else {
            $userIds = User::where('id', '!=', $actorId)->pluck('id');
        }

        foreach ($userIds as $userId) {
            ForumNotification::create([
                'user_id' => $userId,
                'actor_id' => $actorId,
                'group_id' => $groupId,
                'thread_id' => $thread->id,
                'comment_id' => $comment?->id,
                'type' => $type,
                'message' => $message,
            ]);
        }
    }
}
