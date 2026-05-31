<?php

namespace App\Contracts\Repositories;

use App\Models\RecurringExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RecurringExpenseRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): RecurringExpense;
    public function update(RecurringExpense $recurringExpense, array $data): RecurringExpense;
    public function delete(RecurringExpense $recurringExpense): void;
}
