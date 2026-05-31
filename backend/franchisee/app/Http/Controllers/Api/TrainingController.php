<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\TrainingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingServiceInterface $trainingService
    ) {}

    public function elearning(Request $request): JsonResponse
    {
        return response()->json($this->trainingService->elearning($request->user()));
    }

    public function videos(): JsonResponse
    {
        return response()->json($this->trainingService->videos());
    }

    public function marketing(): JsonResponse
    {
        return response()->json($this->trainingService->marketing());
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json($this->trainingService->show($request->user(), $id));
    }

    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,completed',
            'progress_percent' => 'nullable|integer|min:0|max:100',
        ]);

        return response()->json($this->trainingService->updateProgress($request->user(), $id, $validated));
    }

    public function categories(): JsonResponse
    {
        return response()->json($this->trainingService->categories());
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:elearning,videos,marketing',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        return response()->json($this->trainingService->storeCategory($validated), 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:elearning,videos,marketing',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        return response()->json($this->trainingService->updateCategory($id, $validated));
    }

    public function deleteCategory(int $id): JsonResponse
    {
        return response()->json($this->trainingService->deleteCategory($id));
    }

    public function items(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'type']);

        return response()->json($this->trainingService->items($filters));
    }

    public function storeItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
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

        return response()->json($this->trainingService->storeItem($validated), 201);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
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

        return response()->json($this->trainingService->updateItem($id, $validated));
    }

    public function deleteItem(int $id): JsonResponse
    {
        return response()->json($this->trainingService->deleteItem($id));
    }
}
