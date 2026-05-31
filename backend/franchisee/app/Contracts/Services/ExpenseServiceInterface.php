<?php

namespace App\Contracts\Services;

use App\Models\Expense;
use Illuminate\Pagination\LengthAwarePaginator;

interface ExpenseServiceInterface
{
    public function listExpenses(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getExpense(int $id): Expense;

    public function createExpense(array $data): Expense;

    public function updateExpense(Expense $expense, array $data): Expense;

    public function deleteExpense(Expense $expense): bool;

    public function getExpenseHistory(Expense $expense): LengthAwarePaginator;
}
