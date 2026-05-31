<?php

namespace App\Contracts\Services;

use App\Models\ExpenseCategory;
use Illuminate\Support\Collection;

interface ExpenseCategoryServiceInterface
{
    public function all(): Collection;
    public function create(array $data): ExpenseCategory;
    public function update(ExpenseCategory $category, array $data): ExpenseCategory;
    public function delete(ExpenseCategory $category): void;
}
