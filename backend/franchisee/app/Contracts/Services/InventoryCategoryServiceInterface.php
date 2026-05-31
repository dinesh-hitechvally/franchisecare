<?php

namespace App\Contracts\Services;

use App\Models\InventoryCategory;
use Illuminate\Support\Collection;

interface InventoryCategoryServiceInterface
{
    public function all(): Collection;
    public function create(array $data): InventoryCategory;
    public function update(InventoryCategory $category, array $data): InventoryCategory;
    public function delete(InventoryCategory $category): bool;
}
