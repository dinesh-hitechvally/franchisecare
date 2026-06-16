<?php

namespace App\Contracts\Services;

use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;

interface UserProfileServiceInterface
{
    public function show(User $user): array;

    public function userPosts(User $user, ?Authenticatable $currentUser, array $filters): array;

    public function createPost(Authenticatable $user, array $data): ForumThread;

    public function updatePost(Authenticatable $user, ForumThread $thread, array $data): array;

    public function updateProfile(Authenticatable $user, array $data, ?UploadedFile $avatar): array;
}
