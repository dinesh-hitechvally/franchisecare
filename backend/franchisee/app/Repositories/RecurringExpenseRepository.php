<?php

namespace App\Repositories;

use App\Contracts\Repositories\RecurringExpenseRepositoryInterface;
use App\Models\RecurringExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecurringExpenseRepository implements RecurringExpenseRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RecurringExpense::with('category')->latest('start_date');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): RecurringExpense
    {
        return RecurringExpense::create($data);
    }

    public function update(RecurringExpense $recurringExpense, array $data): RecurringExpense
    {
        $recurringExpense->update($data);
        return $recurringExpense;
    }

    public function delete(RecurringExpense $recurringExpense): void
    {
        $recurringExpense->delete();
    }
}
