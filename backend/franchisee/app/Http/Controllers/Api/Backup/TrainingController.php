<?php

namespace App\Http\Controllers\Api\Backup;

use App\Http\Controllers\Controller;
use App\Models\TrainingCategory;
use App\Models\TrainingItem;
use App\Models\TrainingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TrainingController extends Controller
{
    /**
     * Get all e-learning courses grouped by category
     */
    public function elearning(): JsonResponse
    {
        $categories = TrainingCategory::active()
            ->ofType('elearning')
            ->orderBy('sort_order')
            ->with(['items' => function ($query) {
                $query->active()->orderBy('sort_order');
            }])
            ->get();

        // Get user progress for items
        $userId = Auth::id();
        $progressMap = [];
        
        if ($userId) {
            $itemIds = $categories->pluck('items')->flatten()->pluck('id');
            $progress = TrainingProgress::where('user_id', $userId)
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

        // Transform data
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

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get all training videos
     */
    public function videos(): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get marketing resources
     */
    public function marketing(): JsonResponse
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

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get a single training item
     */
    public function show(int $id): JsonResponse
    {
        $item = TrainingItem::with('category')->findOrFail($id);

        // Update last accessed
        if (Auth::id()) {
            TrainingProgress::updateOrCreate(
                ['user_id' => Auth::id(), 'training_item_id' => $id],
                ['last_accessed_at' => now()]
            );
        }

        return response()->json([
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
        ]);
    }

    /**
     * Update training progress
     */
    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:not_started,in_progress,completed',
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ]);

        $progress = TrainingProgress::updateOrCreate(
            ['user_id' => Auth::id(), 'training_item_id' => $id],
            [
                'status' => $request->status,
                'progress_percent' => $request->progress_percent ?? ($request->status === 'completed' ? 100 : 0),
                'started_at' => $request->status !== 'not_started' ? now() : null,
                'completed_at' => $request->status === 'completed' ? now() : null,
                'last_accessed_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Admin: Get all categories
     */
    public function categories(): JsonResponse
    {
        $categories = TrainingCategory::orderBy('type')
            ->orderBy('sort_order')
            ->withCount('items')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Admin: Create category
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:elearning,videos,marketing',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category = TrainingCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'type' => $request->type,
            'icon' => $request->icon,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $category,
        ], 201);
    }

    /**
     * Admin: Update category
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = TrainingCategory::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:elearning,videos,marketing',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only(['name', 'description', 'type', 'icon', 'sort_order', 'is_active']);
        
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'data' => $category->fresh(),
        ]);
    }

    /**
     * Admin: Delete category
     */
    public function deleteCategory(int $id): JsonResponse
    {
        $category = TrainingCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Admin: Get all items
     */
    public function items(Request $request): JsonResponse
    {
        $query = TrainingItem::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Admin: Create item
     */
    public function storeItem(Request $request): JsonResponse
    {
        $request->validate([
            'category_id' => 'nullable|exists:training_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'required|in:course,video,document,link',
            'thumbnail' => 'nullable|string',
            'video_url' => 'nullable|string',
            'document_url' => 'nullable|string',
            'external_url' => 'nullable|string',
            'duration' => 'nullable|string|max:20',
            'duration_minutes' => 'nullable|integer',
            'instructor' => 'nullable|string|max:255',
            'highlights' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $item = TrainingItem::create([
            'category_id' => $request->category_id,
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'content' => $request->content,
            'type' => $request->type,
            'thumbnail' => $request->thumbnail,
            'video_url' => $request->video_url,
            'document_url' => $request->document_url,
            'external_url' => $request->external_url,
            'duration' => $request->duration,
            'duration_minutes' => $request->duration_minutes,
            'instructor' => $request->instructor,
            'highlights' => $request->highlights,
            'sort_order' => $request->sort_order ?? 0,
            'is_featured' => $request->is_featured ?? false,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $item->load('category'),
        ], 201);
    }

    /**
     * Admin: Update item
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $item = TrainingItem::findOrFail($id);

        $request->validate([
            'category_id' => 'nullable|exists:training_categories,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'type' => 'sometimes|in:course,video,document,link',
            'thumbnail' => 'nullable|string',
            'video_url' => 'nullable|string',
            'document_url' => 'nullable|string',
            'external_url' => 'nullable|string',
            'duration' => 'nullable|string|max:20',
            'duration_minutes' => 'nullable|integer',
            'instructor' => 'nullable|string|max:255',
            'highlights' => 'nullable|array',
            'sort_order' => 'nullable|integer',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->only([
            'category_id', 'title', 'description', 'content', 'type',
            'thumbnail', 'video_url', 'document_url', 'external_url',
            'duration', 'duration_minutes', 'instructor', 'highlights',
            'sort_order', 'is_featured', 'is_active',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $item->update($data);

        return response()->json([
            'success' => true,
            'data' => $item->fresh()->load('category'),
        ]);
    }

    /**
     * Admin: Delete item
     */
    public function deleteItem(int $id): JsonResponse
    {
        $item = TrainingItem::findOrFail($id);
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully',
        ]);
    }
}
