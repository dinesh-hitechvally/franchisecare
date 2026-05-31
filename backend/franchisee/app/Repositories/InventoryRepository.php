<?php

namespace App\Repositories;

use App\Contracts\Repositories\InventoryRepositoryInterface;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?InventoryItem
    {
        $query = InventoryItem::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function findByIdOrFail(int $id, array $relations = []): InventoryItem
    {
        $query = InventoryItem::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): InventoryItem
    {
        return InventoryItem::create($data);
    }

    public function update(InventoryItem $inventory, array $data): InventoryItem
    {
        $inventory->update($data);
        return $inventory->fresh(['unit', 'category']);
    }

    public function delete(InventoryItem $inventory): bool
    {
        return $inventory->delete();
    }

    public function adjustStock(InventoryItem $inventory, int $quantity, string $reason): InventoryItem
    {
        $inventory->increment('quantity', $quantity);
        return $inventory->fresh();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = InventoryItem::with(['unit', 'category'])->orderBy('name');

        // Filter by company_id
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Filter by category slug
        if (!empty($filters['category'])) {
            $query->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category']);
            });
        }

        // Filter by category_id
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        // Filter by booking_usage
        if (isset($filters['booking_usage'])) {
            $query->where('booking_usage', $filters['booking_usage']);
        }

        return $query;
    }
}
