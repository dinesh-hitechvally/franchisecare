<?php

namespace App\Contracts\Services;

use App\Models\InventoryOrder;

interface InventoryOrderServiceInterface
{
    public function paginate(array $filters, int $perPage): array;
    public function create(array $data): InventoryOrder;
    public function update(InventoryOrder $order, array $data): array;
    public function delete(InventoryOrder $order): bool;
}
