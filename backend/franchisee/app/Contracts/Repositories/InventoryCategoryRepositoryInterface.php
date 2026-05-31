<?php

namespace App\Contracts\Repositories;

use App\Models\InventoryCategory;
use Illuminate\Support\Collection;

interface InventoryCategoryRepositoryInterface
{
    public function all(int $companyId): Collection;
    public function create(array $data): InventoryCategory;
    public function update(InventoryCategory $category, array $data): InventoryCategory;
    public function delete(InventoryCategory $category): bool;
}
