<?php

namespace App\Contracts\Repositories;

use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;

interface ExpenseCategoryRepositoryInterface
{
    public function all(int $companyId): Collection;
    public function create(array $data): ExpenseCategory;
    public function update(ExpenseCategory $category, array $data): ExpenseCategory;
    public function delete(ExpenseCategory $category): void;
}
