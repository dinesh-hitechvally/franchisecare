<?php

namespace App\Repositories;

use App\Contracts\Repositories\ExpenseCategoryRepositoryInterface;
use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;

class ExpenseCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    public function all(int $companyId): Collection
    {
        return ExpenseCategory::withCount('expenses')
            ->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                  ->orWhere('is_system', true);
            })
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $category->update($data);
        return $category;
    }

    public function delete(ExpenseCategory $category): void
    {
        $category->delete();
    }
}
