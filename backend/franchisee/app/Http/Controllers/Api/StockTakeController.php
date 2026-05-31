<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\StockTakeServiceInterface;
use App\Http\Requests\StockTake\StoreStockTakeRequest;
use Illuminate\Http\JsonResponse;

class StockTakeController extends Controller
{
    public function __construct(
        protected StockTakeServiceInterface $stockTakeService
    ) {}

    public function getLast($categoryId): JsonResponse
    {
        return response()->json($this->stockTakeService->getLastBatch((int) $categoryId));
    }

    public function getHistory($categoryId): JsonResponse
    {
        return response()->json($this->stockTakeService->getHistory((int) $categoryId));
    }

    public function getCurrentSoh($categoryId): JsonResponse
    {
        return response()->json($this->stockTakeService->getCurrentSoh((int) $categoryId));
    }

    public function store(StoreStockTakeRequest $request): JsonResponse
    {
        try {
            $batch = $this->stockTakeService->store(
                $request->input('category_id'),
                $request->input('values', [])
            );

            return response()->json([
                'message' => 'Stock take submitted successfully',
                'data' => $batch,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit stock take',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
