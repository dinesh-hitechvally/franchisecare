<?php

namespace App\Contracts\Services;

use App\Models\Income;
use Illuminate\Pagination\LengthAwarePaginator;

interface IncomeServiceInterface
{
    public function listIncomes(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getIncome(int $id): Income;

    public function createIncome(array $data): Income;

    public function updateIncome(Income $income, array $data): Income;

    public function deleteIncome(Income $income): bool;

    public function getIncomeHistory(Income $income): LengthAwarePaginator;
}
