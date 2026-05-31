<?php

namespace App\Services;

use App\Contracts\Repositories\ExpenseRepositoryInterface;
use App\Contracts\Services\ExpenseServiceInterface;
use App\Models\Expense;
use App\Models\ExpenseAudit;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ExpenseService implements ExpenseServiceInterface
{
    public function __construct(
        private ExpenseRepositoryInterface $expenseRepository
    ) {}

    public function listExpenses(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Add company_id filter from auth
        if (Auth::check() && Auth::user()->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        return $this->expenseRepository->getPaginated($filters, $perPage);
    }

    public function getExpense(int $id): Expense
    {
        return $this->expenseRepository->findByIdOrFail($id, ['category']);
    }

    public function createExpense(array $data): Expense
    {
        $companyId = Auth::user()->company_id;
        $isRecurring = $data['is_recurring'] ?? false;

        // Handle recurring expense first
        if ($isRecurring) {
            $recurringExpense = RecurringExpense::create([
                'company_id' => $companyId,
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'start_date' => $data['expense_date'],
                'frequency' => $data['recurring_frequency'] ?? 'weekly',
                'status' => 'active',
                'auto_extend' => $data['auto_extend_recurring'] ?? false,
                'total' => $data['amount'],
            ]);

            $data['recurring_expense_id'] = $recurringExpense->id;
        }

        // Prepare expense data
        $expenseData = [
            'company_id' => $companyId,
            'expense_category_id' => $data['expense_category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'],
            'expense_date' => $data['expense_date'],
            'is_active' => $data['is_active'] ?? true,
            'recurring_expense_id' => $data['recurring_expense_id'] ?? null,
        ];

        $expense = $this->expenseRepository->create($expenseData);

        return $expense->load('category');
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        $companyId = Auth::user()->company_id;
        $isRecurring = $data['is_recurring'] ?? false;

        // Handle recurring expense update
        if ($isRecurring) {
            if (!$expense->recurring_expense_id) {
                $recurringExpense = RecurringExpense::create([
                    'company_id' => $companyId,
                    'expense_category_id' => $data['expense_category_id'] ?? $expense->expense_category_id,
                    'start_date' => $data['expense_date'] ?? $expense->expense_date,
                    'frequency' => $data['recurring_frequency'] ?? 'weekly',
                    'status' => 'active',
                    'auto_extend' => $data['auto_extend_recurring'] ?? false,
                    'total' => $data['amount'] ?? $expense->amount,
                ]);

                $data['recurring_expense_id'] = $recurringExpense->id;
            } else {
                $expense->recurringExpense->update([
                    'frequency' => $data['recurring_frequency'] ?? 'weekly',
                    'auto_extend' => $data['auto_extend_recurring'] ?? false,
                    'total' => $data['amount'] ?? $expense->amount,
                ]);
            }
        }

        // Prepare update data
        $updateData = [];
        $fields = ['expense_category_id', 'title', 'description', 'amount', 'expense_date', 'is_active', 'recurring_expense_id'];
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        $expense = $this->expenseRepository->update($expense, $updateData);

        return $expense->load('category');
    }

    public function deleteExpense(Expense $expense): bool
    {
        return $this->expenseRepository->delete($expense);
    }

    public function getExpenseHistory(Expense $expense): LengthAwarePaginator
    {
        $history = ExpenseAudit::where('expense_id', $expense->id)
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
