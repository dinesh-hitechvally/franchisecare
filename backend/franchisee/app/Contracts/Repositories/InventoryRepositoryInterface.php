<?php

namespace App\Contracts\Repositories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?Inventory;

    public function findByIdOrFail(int $id, array $relations = []): Inventory;

    public function create(array $data): Inventory;

    public function update(Inventory $inventory, array $data): Inventory;

    public function delete(Inventory $inventory): bool;

    public function adjustStock(Inventory $inventory, int $quantity, string $reason): Inventory;
}
