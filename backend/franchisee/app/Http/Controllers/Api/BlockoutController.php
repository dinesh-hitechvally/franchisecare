<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\BlockoutServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blockout\StoreBlockoutRequest;
use App\Http\Requests\Blockout\UpdateBlockoutRequest;
use App\Models\Blockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID BlockoutController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on BlockoutServiceInterface abstraction
 */
class BlockoutController extends Controller
{
    public function __construct(
        private BlockoutServiceInterface $blockoutService
    ) {}

    /**
     * Display a listing of blockouts.
     */
    public function index(Request $request)
    {
        $filters = [
            'company_id' => $request->get('company_id'),
            'is_recurring' => $request->has('is_recurring') ? $request->boolean('is_recurring') : null,
            'search' => $request->get('search'),
        ];

        $perPage = $request->input('per_page', 25);
        $blockouts = $this->blockoutService->listBlockouts($filters, $perPage);

        return $blockouts;
    }

    /**
     * Store a newly created blockout.
     */
    public function store(StoreBlockoutRequest $request): JsonResponse
    {
        try {
            $blockout = $this->blockoutService->createBlockout($request->blockoutData());
            return response()->json($blockout, 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified blockout.
     */
    public function show(Blockout $blockout): JsonResponse
    {
        return response()->json($blockout);
    }

    /**
     * Update the specified blockout.
     */
    public function update(UpdateBlockoutRequest $request, Blockout $blockout): JsonResponse
    {
        $blockout = $this->blockoutService->updateBlockout($blockout, $request->blockoutData());

        return response()->json($blockout);
    }

    /**
     * Remove the specified blockout.
     */
    public function destroy(Blockout $blockout): JsonResponse
    {
        $this->blockoutService->deleteBlockout($blockout);

        return response()->json(null, 204);
    }

    /**
     * Get audit history for a blockout.
     */
    public function getHistory(Blockout $blockout): JsonResponse
    {
        $history = $this->blockoutService->getBlockoutHistory($blockout);

        return response()->json($history);
    }
}
