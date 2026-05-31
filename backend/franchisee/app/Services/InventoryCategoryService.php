<?php

namespace App\Services;

use App\Contracts\Repositories\InventoryCategoryRepositoryInterface;
use App\Contracts\Services\InventoryCategoryServiceInterface;
use App\Models\InventoryCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InventoryCategoryService implements InventoryCategoryServiceInterface
{
    public function __construct(
        protected InventoryCategoryRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): ?int
    {
        return Auth::user()?->company_id ?? Auth::user()?->franchise_id;
    }

    public function all(): Collection
    {
        return $this->repository->all($this->getCompanyId());
    }

    public function create(array $data): InventoryCategory
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        return $this->repository->create([
            'company_id' => $this->getCompanyId(),
            'name' => $data['name'],
            'slug' => $slug,
            'color' => $data['color'] ?? 'bg-slate-100 text-slate-700',
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);
    }

    public function update(InventoryCategory $category, array $data): InventoryCategory
    {
        return $this->repository->update($category, $data);
    }

    public function delete(InventoryCategory $category): bool
    {
        return $this->repository->delete($category);
    }
}
