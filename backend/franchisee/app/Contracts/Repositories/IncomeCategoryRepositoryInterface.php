<?php

namespace App\Contracts\Repositories;

use App\Models\IncomeCategory;
use Illuminate\Support\Collection;

interface IncomeCategoryRepositoryInterface
{
    public function all(int $companyId): Collection;
    public function create(array $data): IncomeCategory;
    public function update(IncomeCategory $category, array $data): IncomeCategory;
    public function delete(IncomeCategory $category): void;
}
