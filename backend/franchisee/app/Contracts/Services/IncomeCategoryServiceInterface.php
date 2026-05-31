<?php

namespace App\Contracts\Services;

use App\Models\IncomeCategory;
use Illuminate\Support\Collection;

interface IncomeCategoryServiceInterface
{
    public function all(): Collection;
    public function create(array $data): IncomeCategory;
    public function update(IncomeCategory $category, array $data): IncomeCategory;
    public function delete(IncomeCategory $category): void;
}
