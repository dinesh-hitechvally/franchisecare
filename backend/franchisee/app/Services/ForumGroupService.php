<?php

namespace App\Services;

use App\Contracts\Services\ForumGroupServiceInterface;
use App\Models\ForumGroup;
use Illuminate\Contracts\Auth\Authenticatable;

class ForumGroupService implements ForumGroupServiceInterface
{
    public function index(Authenticatable $user, array $filters): array
    {
        $query = ForumGroup::with(['members', 'creator'])
            ->withCount(['members', 'threads']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['my_groups'])) {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->orderBy('type')->orderBy('name')->get()->toArray();
    }

    public function store(Authenticatable $user, array $data): ForumGroup
    {
        $data['created_by'] = $user->id;
        $group = ForumGroup::create($data);

        $group->members()->attach($user->id, ['role' => 'admin']);

        return $group->load(['members', 'creator']);
    }

    public function show(ForumGroup $forumGroup): ForumGroup
    {
        return $forumGroup->load(['members', 'creator', 'threads.author']);
    }

    public function update(Authenticatable $user, ForumGroup $forumGroup, array $data): array
    {
        if ($forumGroup->created_by !== $user->id) {
            return ['success' => false, 'error' => 'Unauthorized', 'status_code' => 403];
        }

        $forumGroup->update($data);

        return ['success' => true, 'data' => $forumGroup->load(['members', 'creator'])];
    }

    public function destroy(Authenticatable $user, ForumGroup $forumGroup): array
    {
        if ($forumGroup->created_by !== $user->id) {
            return ['success' => false, 'error' => 'Unauthorized', 'status_code' => 403];
        }

        $forumGroup->delete();

        return ['success' => true];
    }

    public function join(Authenticatable $user, ForumGroup $forumGroup): array
    {
        if ($forumGroup->isMember($user->id)) {
            return ['success' => false, 'error' => 'Already a member', 'status_code' => 400];
        }

        $forumGroup->members()->attach($user->id, ['role' => 'member']);

        return ['success' => true, 'message' => 'Joined successfully'];
    }

    public function leave(Authenticatable $user, ForumGroup $forumGroup): array
    {
        if (!$forumGroup->isMember($user->id)) {
            return ['success' => false, 'error' => 'Not a member', 'status_code' => 400];
        }

        $forumGroup->members()->detach($user->id);

        return ['success' => true, 'message' => 'Left successfully'];
    }

    public function members(ForumGroup $forumGroup): array
    {
        return $forumGroup->members()->get()->toArray();
    }
}
