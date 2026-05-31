<?php

namespace App\Repositories;

use App\Contracts\Repositories\InventoryCategoryRepositoryInterface;
use App\Models\InventoryCategory;
use Illuminate\Support\Collection;

class InventoryCategoryRepository implements InventoryCategoryRepositoryInterface
{
    public function all(int $companyId): Collection
    {
        return InventoryCategory::query()
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $companyId);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): InventoryCategory
    {
        return InventoryCategory::create($data);
    }

    public function update(InventoryCategory $category, array $data): InventoryCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(InventoryCategory $category): bool
    {
        if ($category->items()->count() > 0) {
            return false;
        }
        
        $category->delete();
        return true;
    }
}
