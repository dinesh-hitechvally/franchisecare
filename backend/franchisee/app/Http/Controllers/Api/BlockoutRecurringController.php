<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\BlockoutRecurringServiceInterface;
use App\Http\Requests\BlockoutRecurring\StoreBlockoutRecurringRequest;
use App\Http\Requests\BlockoutRecurring\UpdateBlockoutRecurringRequest;
use App\Models\BlockoutRecurring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockoutRecurringController extends Controller
{
    public function __construct(
        protected BlockoutRecurringServiceInterface $blockoutRecurringService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['company_id', 'search']);
        $perPage = $request->input('per_page', 25);

        return response()->json($this->blockoutRecurringService->paginate($filters, $perPage));
    }

    public function store(StoreBlockoutRecurringRequest $request): JsonResponse
    {
        try {
            $recurring = $this->blockoutRecurringService->create($request->validated());
            return response()->json($recurring, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(BlockoutRecurring $blockoutRecurring): JsonResponse
    {
        return response()->json($blockoutRecurring);
    }

    public function update(UpdateBlockoutRecurringRequest $request, BlockoutRecurring $blockoutRecurring): JsonResponse
    {
        $recurring = $this->blockoutRecurringService->update($blockoutRecurring, $request->validated());
        return response()->json($recurring);
    }

    public function destroy(BlockoutRecurring $blockoutRecurring): JsonResponse
    {
        $this->blockoutRecurringService->delete($blockoutRecurring);
        return response()->json(null, 204);
    }

    public function getHistory(BlockoutRecurring $blockoutRecurring): JsonResponse
    {
        return response()->json($this->blockoutRecurringService->getHistory($blockoutRecurring));
    }
}
