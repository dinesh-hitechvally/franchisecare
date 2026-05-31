<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\InventoryCategoryServiceInterface;
use App\Http\Requests\InventoryCategory\StoreInventoryCategoryRequest;
use App\Http\Requests\InventoryCategory\UpdateInventoryCategoryRequest;
use App\Models\InventoryCategory;
use Illuminate\Http\JsonResponse;

class InventoryCategoryController extends Controller
{
    public function __construct(
        protected InventoryCategoryServiceInterface $inventoryCategoryService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->inventoryCategoryService->all());
    }

    public function store(StoreInventoryCategoryRequest $request): JsonResponse
    {
        $category = $this->inventoryCategoryService->create($request->validated());
        return response()->json($category, 201);
    }

    public function update(UpdateInventoryCategoryRequest $request, InventoryCategory $inventoryCategory): JsonResponse
    {
        $category = $this->inventoryCategoryService->update($inventoryCategory, $request->validated());
        return response()->json($category);
    }

    public function destroy(InventoryCategory $inventoryCategory): JsonResponse
    {
        $deleted = $this->inventoryCategoryService->delete($inventoryCategory);
        
        if (!$deleted) {
            return response()->json([
                'message' => 'Cannot delete category with existing items'
            ], 422);
        }

        return response()->json(null, 204);
    }
}
