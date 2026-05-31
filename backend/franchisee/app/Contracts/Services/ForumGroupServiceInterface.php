<?php

namespace App\Contracts\Services;

use App\Models\ForumGroup;
use Illuminate\Contracts\Auth\Authenticatable;

interface ForumGroupServiceInterface
{
    public function index(Authenticatable $user, array $filters): array;

    public function store(Authenticatable $user, array $data): ForumGroup;

    public function show(ForumGroup $forumGroup): ForumGroup;

    public function update(Authenticatable $user, ForumGroup $forumGroup, array $data): array;

    public function destroy(Authenticatable $user, ForumGroup $forumGroup): array;

    public function join(Authenticatable $user, ForumGroup $forumGroup): array;

    public function leave(Authenticatable $user, ForumGroup $forumGroup): array;

    public function members(ForumGroup $forumGroup): array;
}
