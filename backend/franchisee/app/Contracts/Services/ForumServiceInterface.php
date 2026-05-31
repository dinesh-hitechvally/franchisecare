<?php

namespace App\Contracts\Services;

use App\Models\ForumComment;
use App\Models\ForumNotification;
use App\Models\ForumThread;
use Illuminate\Contracts\Auth\Authenticatable;

interface ForumServiceInterface
{
    public function index(?Authenticatable $user, array $filters): array;

    public function store(Authenticatable $user, array $data): ForumThread;

    public function show(?Authenticatable $user, ForumThread $forumThread): ForumThread;

    public function destroy(Authenticatable $user, ForumThread $forumThread): array;

    public function addComment(Authenticatable $user, ForumThread $forumThread, string $content): ForumComment;

    public function like(Authenticatable $user, ForumThread $forumThread): array;

    public function likeComment(Authenticatable $user, ForumComment $forumComment): array;

    public function replyToComment(Authenticatable $user, ForumComment $forumComment, string $content): ForumComment;

    public function notifications(Authenticatable $user, array $filters): array;

    public function markAllNotificationsAsRead(Authenticatable $user, array $filters): array;

    public function markNotificationAsRead(Authenticatable $user, ForumNotification $forumNotification): array;
}
