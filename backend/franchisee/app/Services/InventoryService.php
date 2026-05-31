<?php

namespace App\Services;

use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Contracts\Services\InventoryServiceInterface;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class InventoryService implements InventoryServiceInterface
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function listInventory(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Add company_id filter from auth
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;
        if ($companyId) {
            $filters['company_id'] = $companyId;
        }

        return $this->inventoryRepository->getPaginated($filters, $perPage);
    }

    public function getInventory(int $id): InventoryItem
    {
        return $this->inventoryRepository->findByIdOrFail($id, ['unit', 'category']);
    }

    public function createInventory(array $data): InventoryItem
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        // Handle unit - find or create
        $unitName = $data['unit'] ?? 'units';
        $unit = Unit::firstOrCreate(
            ['name' => $unitName],
            ['name' => $unitName]
        );

        // Handle category - by id or slug
        $categoryId = $data['category_id'] ?? null;
        if (!$categoryId && !empty($data['category'])) {
            $category = InventoryCategory::firstOrCreate(
                ['slug' => $data['category']],
                [
                    'name' => ucfirst($data['category']),
                    'slug' => $data['category'],
                    'color' => 'bg-slate-100 text-slate-700',
                ]
            );
            $categoryId = $category->id;
        }

        $inventoryData = [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'quantity' => $data['quantity'],
            'min_stock' => $data['min_stock'] ?? 0,
            'unit_price' => $data['unit_price'] ?? 0,
            'unit_id' => $unit->id,
            'notes' => $data['notes'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'booking_usage' => $data['booking_usage'] ?? false,
        ];

        $item = $this->inventoryRepository->create($inventoryData);
        return $item->load(['unit', 'category']);
    }

    public function updateInventory(InventoryItem $inventory, array $data): InventoryItem
    {
        $updateData = [];

        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (array_key_exists('sku', $data)) $updateData['sku'] = $data['sku'];
        if (isset($data['quantity'])) $updateData['quantity'] = $data['quantity'];
        if (array_key_exists('min_stock', $data)) $updateData['min_stock'] = $data['min_stock'] ?? 0;
        if (array_key_exists('unit_price', $data)) $updateData['unit_price'] = $data['unit_price'] ?? 0;
        if (array_key_exists('notes', $data)) $updateData['notes'] = $data['notes'];
        if (isset($data['is_active'])) $updateData['is_active'] = $data['is_active'];
        if (isset($data['booking_usage'])) $updateData['booking_usage'] = $data['booking_usage'];

        // Handle category - by id or slug
        if (isset($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        } elseif (!empty($data['category'])) {
            $category = InventoryCategory::firstOrCreate(
                ['slug' => $data['category']],
                [
                    'name' => ucfirst($data['category']),
                    'slug' => $data['category'],
                    'color' => 'bg-slate-100 text-slate-700',
                ]
            );
            $updateData['category_id'] = $category->id;
        }

        // Handle unit
        if (isset($data['unit'])) {
            $unit = Unit::firstOrCreate(
                ['name' => $data['unit']],
                ['name' => $data['unit']]
            );
            $updateData['unit_id'] = $unit->id;
        }

        return $this->inventoryRepository->update($inventory, $updateData);
    }

    public function deleteInventory(InventoryItem $inventory): bool
    {
        return $this->inventoryRepository->delete($inventory);
    }

    public function adjustStock(InventoryItem $inventory, int $quantity, string $reason): InventoryItem
    {
        return $this->inventoryRepository->adjustStock($inventory, $quantity, $reason);
    }

    public function getInventoryHistory(InventoryItem $inventory): LengthAwarePaginator
    {
        // Placeholder - implement if there's an InventoryAudit model
        return new LengthAwarePaginator([], 0, 10);
    }

    public function formatInventoryItem(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'company_id' => $item->company_id,
            'category_id' => $item->category_id,
            'category' => $item->category?->slug,
            'category_name' => $item->category?->name,
            'category_color' => $item->category?->color,
            'name' => $item->name,
            'sku' => $item->sku,
            'quantity' => $item->quantity,
            'min_stock' => $item->min_stock,
            'unit_price' => $item->unit_price,
            'unit_id' => $item->unit_id,
            'unit' => $item->unit?->name ?? 'units',
            'notes' => $item->notes,
            'is_active' => $item->is_active,
            'booking_usage' => $item->booking_usage,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }
}
