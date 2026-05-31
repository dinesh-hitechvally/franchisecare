<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\NewsServiceInterface;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct(
        protected NewsServiceInterface $newsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['is_published', 'category', 'search', 'per_page']);

        return response()->json($this->newsService->index($filters));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $news = $this->newsService->store($request->user(), $validated);

        return response()->json($news, 201);
    }

    public function show(News $news): JsonResponse
    {
        return response()->json($this->newsService->show($news));
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'category' => 'sometimes|string',
            'is_published' => 'sometimes|boolean',
        ]);

        return response()->json($this->newsService->update($news, $validated));
    }

    public function destroy(News $news): JsonResponse
    {
        $this->newsService->destroy($news);

        return response()->json(null, 204);
    }

    public function publish(News $news): JsonResponse
    {
        return response()->json($this->newsService->publish($news));
    }
}
