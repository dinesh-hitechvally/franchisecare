<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\InventoryOrderServiceInterface;
use App\Http\Requests\InventoryOrder\StoreInventoryOrderRequest;
use App\Http\Requests\InventoryOrder\UpdateInventoryOrderRequest;
use App\Models\InventoryOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryOrderController extends Controller
{
    public function __construct(
        protected InventoryOrderServiceInterface $inventoryOrderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'status']);
        $perPage = (int) $request->input('per_page', 25);

        return response()->json($this->inventoryOrderService->paginate($filters, $perPage));
    }

    public function store(StoreInventoryOrderRequest $request): JsonResponse
    {
        $order = $this->inventoryOrderService->create($request->validated());

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function show(InventoryOrder $inventoryOrder): JsonResponse
    {
        $inventoryOrder->load(['items', 'user']);
        return response()->json($inventoryOrder);
    }

    public function update(UpdateInventoryOrderRequest $request, InventoryOrder $inventoryOrder): JsonResponse
    {
        $result = $this->inventoryOrderService->update($inventoryOrder, $request->validated());
        return response()->json($result);
    }

    public function destroy(InventoryOrder $inventoryOrder): JsonResponse
    {
        $deleted = $this->inventoryOrderService->delete($inventoryOrder);

        if (!$deleted) {
            return response()->json([
                'message' => 'Only pending orders can be deleted',
            ], 422);
        }

        return response()->json(null, 204);
    }
}
