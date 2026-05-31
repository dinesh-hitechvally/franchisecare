<?php

namespace App\Contracts\Services;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryServiceInterface
{
    public function listInventory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getInventory(int $id): InventoryItem;

    public function createInventory(array $data): InventoryItem;

    public function updateInventory(InventoryItem $inventory, array $data): InventoryItem;

    public function deleteInventory(InventoryItem $inventory): bool;

    public function adjustStock(InventoryItem $inventory, int $quantity, string $reason): InventoryItem;

    public function getInventoryHistory(InventoryItem $inventory): LengthAwarePaginator;

    public function formatInventoryItem(InventoryItem $item): array;
}
