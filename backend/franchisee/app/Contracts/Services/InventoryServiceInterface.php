<?php

namespace App\Contracts\Services;

use App\Models\Inventory;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryServiceInterface
{
    public function listInventory(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getInventory(int $id): Inventory;

    public function createInventory(array $data): Inventory;

    public function updateInventory(Inventory $inventory, array $data): Inventory;

    public function deleteInventory(Inventory $inventory): bool;

    public function adjustStock(Inventory $inventory, int $quantity, string $reason): Inventory;

    public function getInventoryHistory(Inventory $inventory): LengthAwarePaginator;
}
