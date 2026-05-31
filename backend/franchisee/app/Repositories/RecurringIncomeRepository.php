<?php

namespace App\Repositories;

use App\Contracts\Repositories\RecurringIncomeRepositoryInterface;
use App\Models\RecurringIncome;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RecurringIncomeRepository implements RecurringIncomeRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RecurringIncome::with('category')->latest('start_date');

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): RecurringIncome
    {
        return RecurringIncome::create($data);
    }

    public function update(RecurringIncome $recurringIncome, array $data): RecurringIncome
    {
        $recurringIncome->update($data);
        return $recurringIncome;
    }

    public function delete(RecurringIncome $recurringIncome): void
    {
        $recurringIncome->delete();
    }
}
