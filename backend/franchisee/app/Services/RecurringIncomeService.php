<?php

namespace App\Services;

use App\Contracts\Repositories\RecurringIncomeRepositoryInterface;
use App\Contracts\Services\RecurringIncomeServiceInterface;
use App\Models\RecurringIncome;
use Illuminate\Support\Facades\Auth;

class RecurringIncomeService implements RecurringIncomeServiceInterface
{
    public function __construct(
        protected RecurringIncomeRepositoryInterface $repository
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

    public function create(array $data): RecurringIncome
    {
        $data['company_id'] = Auth::user()->company_id;
        $data['status'] = 'active';

        $recurringIncome = $this->repository->create($data);

        return $recurringIncome->load('category');
    }

    public function update(RecurringIncome $recurringIncome, array $data): RecurringIncome
    {
        $this->repository->update($recurringIncome, $data);

        return $recurringIncome->load('category');
    }

    public function delete(RecurringIncome $recurringIncome): void
    {
        $this->repository->delete($recurringIncome);
    }
}
