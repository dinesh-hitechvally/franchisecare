<?php

namespace App\Services;

use App\Contracts\Repositories\RecurringExpenseRepositoryInterface;
use App\Contracts\Services\RecurringExpenseServiceInterface;
use App\Models\RecurringExpense;
use Illuminate\Support\Facades\Auth;

class RecurringExpenseService implements RecurringExpenseServiceInterface
{
    public function __construct(
        protected RecurringExpenseRepositoryInterface $repository
    ) {}

    public function paginate(array $filters, int $perPage): array
    {
        if (Auth::check() && Auth::user()->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        $paginator = $this->repository->paginate($filters, $perPage);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }

    public function create(array $data): RecurringExpense
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['status'] = 'ACTIVE';

        $recurringExpense = $this->repository->create($data);

        return $recurringExpense->load('category');
    }

    public function update(RecurringExpense $recurringExpense, array $data): RecurringExpense
    {
        $this->repository->update($recurringExpense, $data);

        return $recurringExpense->load('category');
    }

    public function delete(RecurringExpense $recurringExpense): void
    {
        $this->repository->delete($recurringExpense);
    }
}
