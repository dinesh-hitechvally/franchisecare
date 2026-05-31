<?php

namespace App\Contracts\Repositories;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?InventoryItem;

    public function findByIdOrFail(int $id, array $relations = []): InventoryItem;

    public function create(array $data): InventoryItem;

    public function update(InventoryItem $inventory, array $data): InventoryItem;

    public function delete(InventoryItem $inventory): bool;

    public function adjustStock(InventoryItem $inventory, int $quantity, string $reason): InventoryItem;
}
