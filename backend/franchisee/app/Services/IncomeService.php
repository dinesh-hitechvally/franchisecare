<?php

namespace App\Services;

use App\Contracts\Repositories\IncomeRepositoryInterface;
use App\Contracts\Services\IncomeServiceInterface;
use App\Models\Income;
use App\Models\IncomeAudit;
use App\Models\RecurringIncome;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class IncomeService implements IncomeServiceInterface
{
    public function __construct(
        private IncomeRepositoryInterface $incomeRepository
    ) {}

    public function listIncomes(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Add company_id filter from auth
        if (Auth::check() && Auth::user()->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        return $this->incomeRepository->getPaginated($filters, $perPage);
    }

    public function getIncome(int $id): Income
    {
        return $this->incomeRepository->findByIdOrFail($id, ['category']);
    }

    public function createIncome(array $data): Income
    {
        $companyId = Auth::user()->company_id;
        $isRecurring = $data['is_recurring'] ?? false;

        // Handle recurring income first
        if ($isRecurring) {
            $recurringIncome = RecurringIncome::create([
                'company_id' => $companyId,
                'income_category_id' => $data['income_category_id'] ?? null,
                'start_date' => $data['income_date'],
                'frequency' => $data['recurring_frequency'] ?? 'weekly',
                'status' => 'active',
                'auto_extend' => $data['auto_extend_recurring'] ?? false,
                'total' => $data['amount'],
            ]);

            $data['recurring_income_id'] = $recurringIncome->id;
        }

        // Prepare income data
        $incomeData = [
            'company_id' => $companyId,
            'income_category_id' => $data['income_category_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'income_date' => $data['income_date'],
            'is_active' => $data['is_active'] ?? true,
            'recurring_income_id' => $data['recurring_income_id'] ?? null,
        ];

        $income = $this->incomeRepository->create($incomeData);

        return $income->load('category');
    }

    public function updateIncome(Income $income, array $data): Income
    {
        // Check if income is linked to a booking
        if ($income->booking_id) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Income linked to a booking must be edited via the booking.');
        }

        $companyId = Auth::user()->company_id;
        $isRecurring = $data['is_recurring'] ?? false;

        // Handle recurring income update
        if ($isRecurring) {
            if (!$income->recurring_income_id) {
                $recurringIncome = RecurringIncome::create([
                    'company_id' => $companyId,
                    'income_category_id' => $data['income_category_id'] ?? $income->income_category_id,
                    'start_date' => $data['income_date'] ?? $income->income_date,
                    'frequency' => $data['recurring_frequency'] ?? 'weekly',
                    'status' => 'active',
                    'auto_extend' => $data['auto_extend_recurring'] ?? false,
                    'total' => $data['amount'] ?? $income->amount,
                ]);

                $data['recurring_income_id'] = $recurringIncome->id;
            } else {
                $income->recurringIncome->update([
                    'frequency' => $data['recurring_frequency'] ?? 'weekly',
                    'auto_extend' => $data['auto_extend_recurring'] ?? false,
                    'total' => $data['amount'] ?? $income->amount,
                ]);
            }
        }

        // Prepare update data
        $updateData = [];
        $fields = ['income_category_id', 'title', 'description', 'amount', 'income_date', 'is_active', 'recurring_income_id'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $income = $this->incomeRepository->update($income, $updateData);

        return $income->load('category');
    }

    public function deleteIncome(Income $income): bool
    {
        return $this->incomeRepository->delete($income);
    }

    public function getIncomeHistory(Income $income): LengthAwarePaginator
    {
        $history = IncomeAudit::where('income_id', $income->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);

        $userIds = $history->getCollection()->pluck('performed_by')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'first_name', 'last_name'])->keyBy('id');

        $history->setCollection($history->getCollection()->map(function ($audit) use ($users) {
            $user = $users->get($audit->performed_by);
            if (!$user) {
                return $audit;
            }

            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $audit->performed_by_name = $fullName !== '' ? $fullName : ($user->name ?? null);

            return $audit;
        }));

        return $history;
    }
}
