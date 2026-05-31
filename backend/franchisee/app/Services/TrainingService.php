<?php

namespace App\Services;

use App\Contracts\Services\TrainingServiceInterface;
use App\Models\TrainingCategory;
use App\Models\TrainingItem;
use App\Models\TrainingProgress;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class TrainingService implements TrainingServiceInterface
{
    public function elearning(?Authenticatable $user): array
    {
        $categories = TrainingCategory::active()
            ->ofType('elearning')
            ->orderBy('sort_order')
            ->with(['items' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->get();

        $progressMap = [];
        if ($user) {
            $itemIds = $categories->pluck('items')->flatten()->pluck('id');
            $progress = TrainingProgress::where('user_id', $user->id)
                ->whereIn('training_item_id', $itemIds)
                ->get()
                ->keyBy('training_item_id');

            foreach ($progress as $p) {
                $progressMap[$p->training_item_id] = [
                    'status' => $p->status,
                    'progress_percent' => $p->progress_percent,
                    'completed' => $p->status === 'completed',
                ];
            }
        }

        $data = $categories->map(function ($category) use ($progressMap) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'courses' => $category->items->map(function ($item) use ($progressMap) {
                    $progress = $progressMap[$item->id] ?? null;
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'duration' => $item->duration,
                        'thumbnail' => $item->thumbnail,
                        'video_url' => $item->video_url,
                        'completed' => $progress['completed'] ?? false,
                        'progress' => $progress['progress_percent'] ?? 0,
                    ];
                }),
            ];
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function videos(): array
    {
        $items = TrainingItem::active()
            ->whereHas('category', function ($query) {
                $query->ofType('videos');
            })
            ->orderBy('sort_order')
            ->get();

        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'duration' => $item->duration,
                'thumbnail' => $item->thumbnail,
                'video_url' => $item->video_url,
            ];
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function marketing(): array
    {
        $categories = TrainingCategory::active()
            ->ofType('marketing')
            ->orderBy('sort_order')
            ->with(['items' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->get();

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'items' => $category->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'content' => $item->content,
                        'type' => $item->type,
                        'external_url' => $item->external_url,
                        'instructor' => $item->instructor,
                        'highlights' => $item->highlights,
                    ];
                }),
            ];
        });

        return [
            'success' => true,
            'data' => $data,
        ];
    }

    public function show(?Authenticatable $user, int $id): array
    {
        $item = TrainingItem::with('category')->findOrFail($id);

        if ($user) {
            TrainingProgress::updateOrCreate(
                ['user_id' => $user->id, 'training_item_id' => $id],
                ['last_accessed_at' => now()]
            );
        }

        return [
            'success' => true,
            'data' => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'content' => $item->content,
                'type' => $item->type,
                'thumbnail' => $item->thumbnail,
                'video_url' => $item->video_url,
                'document_url' => $item->document_url,
                'external_url' => $item->external_url,
                'duration' => $item->duration,
                'instructor' => $item->instructor,
                'highlights' => $item->highlights,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                ] : null,
            ],
        ];
    }

    public function updateProgress(Authenticatable $user, int $id, array $data): array
    {
        $progress = TrainingProgress::updateOrCreate(
            ['user_id' => $user->id, 'training_item_id' => $id],
            [
                'status' => $data['status'],
                'progress_percent' => $data['progress_percent'] ?? ($data['status'] === 'completed' ? 100 : 0),
                'started_at' => $data['status'] !== 'not_started' ? now() : null,
                'completed_at' => $data['status'] === 'completed' ? now() : null,
                'last_accessed_at' => now(),
            ]
        );

        return [
            'success' => true,
            'data' => $progress,
        ];
    }

    public function categories(): array
    {
        $categories = TrainingCategory::orderBy('type')
            ->orderBy('sort_order')
            ->withCount('items')
            ->get();

        return [
            'success' => true,
            'data' => $categories,
        ];
    }

    public function storeCategory(array $data): array
    {
        $category = TrainingCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return [
            'success' => true,
            'data' => $category,
        ];
    }

    public function updateCategory(int $id, array $data): array
    {
        $category = TrainingCategory::findOrFail($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return [
            'success' => true,
            'data' => $category->fresh(),
        ];
    }

    public function deleteCategory(int $id): array
    {
        $category = TrainingCategory::findOrFail($id);
        $category->delete();

        return [
            'success' => true,
            'message' => 'Category deleted successfully',
        ];
    }

    public function items(array $filters): array
    {
        $query = TrainingItem::with('category');

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $items = $query->orderBy('sort_order')->get();

        return [
            'success' => true,
            'data' => $items,
        ];
    }

    public function storeItem(array $data): array
    {
        $item = TrainingItem::create([
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'description' => $data['description'] ?? null,
            'content' => $data['content'] ?? null,
            'type' => $data['type'],
            'thumbnail' => $data['thumbnail'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'document_url' => $data['document_url'] ?? null,
            'external_url' => $data['external_url'] ?? null,
            'duration' => $data['duration'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'instructor' => $data['instructor'] ?? null,
            'highlights' => $data['highlights'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_featured' => $data['is_featured'] ?? false,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return [
            'success' => true,
            'data' => $item->load('category'),
        ];
    }

    public function updateItem(int $id, array $data): array
    {
        $item = TrainingItem::findOrFail($id);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $item->update($data);

        return [
            'success' => true,
            'data' => $item->fresh()->load('category'),
        ];
    }

    public function deleteItem(int $id): array
    {
        $item = TrainingItem::findOrFail($id);
        $item->delete();

        return [
            'success' => true,
            'message' => 'Item deleted successfully',
        ];
    }
}
