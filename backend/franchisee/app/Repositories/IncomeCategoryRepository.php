<?php

namespace App\Repositories;

use App\Contracts\Repositories\IncomeCategoryRepositoryInterface;
use App\Models\IncomeCategory;
use Illuminate\Support\Collection;

class IncomeCategoryRepository implements IncomeCategoryRepositoryInterface
{
    public function all(int $companyId): Collection
    {
        return IncomeCategory::withCount('incomes')
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhere('is_system', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): IncomeCategory
    {
        return IncomeCategory::create($data);
    }

    public function update(IncomeCategory $category, array $data): IncomeCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(IncomeCategory $category): void
    {
        $category->delete();
    }
}
