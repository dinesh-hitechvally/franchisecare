<?php

namespace App\Contracts\Repositories;

use App\Models\RecurringIncome;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RecurringIncomeRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): RecurringIncome;
    public function update(RecurringIncome $recurringIncome, array $data): RecurringIncome;
    public function delete(RecurringIncome $recurringIncome): void;
}
