<?php

namespace App\Services;

use App\Contracts\Services\NewsServiceInterface;
use App\Models\News;
use Illuminate\Contracts\Auth\Authenticatable;

class NewsService implements NewsServiceInterface
{
    public function index(array $filters): array
    {
        $query = News::with('author');

        if (isset($filters['is_published'])) {
            $query->where('is_published', $filters['is_published']);
        }

        if (!empty($filters['category']) && $filters['category'] !== 'All News') {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('content', 'like', $term);
            });
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage)->toArray();
    }

    public function store(Authenticatable $user, array $data): News
    {
        $data['author_id'] = $user->id;

        if (!empty($data['is_published'])) {
            $data['published_at'] = now();
        }

        return News::create($data);
    }

    public function show(News $news): News
    {
        return $news->load('author');
    }

    public function update(News $news, array $data): News
    {
        if (!empty($data['is_published']) && !$news->is_published) {
            $data['published_at'] = now();
        }

        $news->update($data);

        return $news;
    }

    public function destroy(News $news): bool
    {
        return $news->delete();
    }

    public function publish(News $news): News
    {
        $news->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return $news;
    }
}
