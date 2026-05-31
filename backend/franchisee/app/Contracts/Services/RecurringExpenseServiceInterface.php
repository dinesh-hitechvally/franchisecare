<?php

namespace App\Contracts\Services;

use App\Models\RecurringExpense;

interface RecurringExpenseServiceInterface
{
    public function paginate(array $filters, int $perPage): array;
    public function create(array $data): RecurringExpense;
    public function update(RecurringExpense $recurringExpense, array $data): RecurringExpense;
    public function delete(RecurringExpense $recurringExpense): void;
}
