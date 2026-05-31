<?php

namespace App\Repositories;

use App\Contracts\Repositories\IncomeRepositoryInterface;
use App\Models\Income;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class IncomeRepository implements IncomeRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    public function findById(int $id, array $relations = []): ?Income
    {
        $query = Income::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function findByIdOrFail(int $id, array $relations = []): Income
    {
        $query = Income::query();
        if (!empty($relations)) {
            $query->with($relations);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data): Income
    {
        return Income::create($data);
    }

    public function update(Income $income, array $data): Income
    {
        $income->update($data);
        return $income->fresh();
    }

    public function delete(Income $income): bool
    {
        return $income->delete();
    }

    private function buildQuery(array $filters = []): Builder
    {
        $query = Income::with('category')->latest('income_date');

        // Filter by company_id
        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        // Date filters
        if (!empty($filters['date_from'])) {
            $query->where('income_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('income_date', '<=', $filters['date_to']);
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('income_category_id', $filters['category_id']);
        }

        return $query;
    }
}
