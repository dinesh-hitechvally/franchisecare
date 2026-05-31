<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\InventoryServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Requests\Inventory\UpdateInventoryRequest;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID InventoryController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on InventoryServiceInterface abstraction
 */
class InventoryController extends Controller
{
    public function __construct(
        private InventoryServiceInterface $inventoryService
    ) {}

    /**
     * Display a listing of inventory items.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'category' => $request->input('category'),
            'category_id' => $request->input('category_id'),
            'search' => $request->input('search'),
            'booking_usage' => $request->filled('booking_usage')
                ? filter_var($request->input('booking_usage'), FILTER_VALIDATE_BOOLEAN)
                : null,
        ];

        // Get all items (no pagination for this endpoint based on original)
        $items = $this->inventoryService->listInventory($filters, 1000);

        $formattedItems = $items->getCollection()->map(function ($item) {
            return $this->inventoryService->formatInventoryItem($item);
        });

        return response()->json($formattedItems);
    }

    /**
     * Store a newly created inventory item.
     */
    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $item = $this->inventoryService->createInventory($request->inventoryData());

        return response()->json($this->inventoryService->formatInventoryItem($item), 201);
    }

    /**
     * Update the specified inventory item.
     */
    public function update(UpdateInventoryRequest $request, InventoryItem $inventoryItem): JsonResponse
    {
        $item = $this->inventoryService->updateInventory($inventoryItem, $request->inventoryData());

        return response()->json($this->inventoryService->formatInventoryItem($item));
    }

    /**
     * Remove the specified inventory item.
     */
    public function destroy(InventoryItem $inventoryItem): JsonResponse
    {
        $this->inventoryService->deleteInventory($inventoryItem);

        return response()->json(null, 204);
    }
}