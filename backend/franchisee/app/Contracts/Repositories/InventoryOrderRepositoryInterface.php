<?php

namespace App\Contracts\Repositories;

use App\Models\InventoryOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryOrderRepositoryInterface
{
    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): InventoryOrder;
    public function update(InventoryOrder $order, array $data): InventoryOrder;
    public function delete(InventoryOrder $order): void;
}
