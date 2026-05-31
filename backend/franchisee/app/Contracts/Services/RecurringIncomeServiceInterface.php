<?php

namespace App\Contracts\Services;

use App\Models\RecurringIncome;

interface RecurringIncomeServiceInterface
{
    public function paginate(array $filters, int $perPage): array;
    public function create(array $data): RecurringIncome;
    public function update(RecurringIncome $recurringIncome, array $data): RecurringIncome;
    public function delete(RecurringIncome $recurringIncome): void;
}
