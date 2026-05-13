<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category')->latest('expense_date');

        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1)));

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string',
            'amount'              => 'required|numeric|min:0',
            'expense_date'        => 'required|date',
            'is_active'           => 'boolean',
            'is_recurring'        => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'auto_extend_recurring' => 'boolean',
        ]);

        $company_id = auth()->user()->company_id;

        // Handle recurring expense first
        if ($request->boolean('is_recurring')) {
            $recurringExpense = RecurringExpense::create([
                'company_id'          => $company_id,
                'expense_category_id' => $validated['expense_category_id'] ?? null,
                'start_date'          => $validated['expense_date'],
                'frequency'           => $request->input('recurring_frequency', 'weekly'),
                'status'              => 'active',
                'auto_extend'         => $request->boolean('auto_extend_recurring', false),
                'total'               => $validated['amount'],
            ]);

            $validated['recurring_expense_id'] = $recurringExpense->id;
        }

        // Prepare expense data - only include fillable fields
        $expenseData = [
            'company_id'          => $company_id,
            'expense_category_id' => $validated['expense_category_id'] ?? null,
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'amount'              => $validated['amount'],
            'expense_date'        => $validated['expense_date'],
            'is_active'           => $validated['is_active'] ?? true,
            'recurring_expense_id' => $validated['recurring_expense_id'] ?? null,
        ];

        $expense = Expense::create($expenseData);

        return response()->json($expense->load('category'), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load('category'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'title'               => 'sometimes|required|string|max:255',
            'description'         => 'nullable|string',
            'amount'              => 'sometimes|required|numeric|min:0',
            'expense_date'        => 'sometimes|required|date',
            'is_active'           => 'boolean',
            'is_recurring'        => 'boolean',
            'recurring_frequency' => 'nullable|in:daily,weekly,monthly,yearly',
            'auto_extend_recurring' => 'boolean',
        ]);

        $company_id = auth()->user()->company_id;

        // Handle recurring expense update
        if ($request->boolean('is_recurring')) {
            if (!$expense->recurring_expense_id) {
                // Create new recurring record if not already created
                $recurringExpense = RecurringExpense::create([
                    'company_id'          => $company_id,
                    'expense_category_id' => $validated['expense_category_id'] ?? $expense->expense_category_id,
                    'start_date'          => $validated['expense_date'] ?? $expense->expense_date,
                    'frequency'           => $request->input('recurring_frequency', 'weekly'),
                    'status'              => 'active',
                    'auto_extend'         => $request->boolean('auto_extend_recurring', false),
                    'total'               => $validated['amount'] ?? $expense->amount,
                ]);

                $validated['recurring_expense_id'] = $recurringExpense->id;
            } else {
                // Update existing recurring record
                $expense->recurringExpense->update([
                    'frequency'   => $request->input('recurring_frequency', 'weekly'),
                    'auto_extend' => $request->boolean('auto_extend_recurring', false),
                    'total'       => $validated['amount'] ?? $expense->amount,
                ]);
            }
        }

        // Prepare update data - only include fillable fields
        $updateData = [];
        if (isset($validated['expense_category_id'])) {
            $updateData['expense_category_id'] = $validated['expense_category_id'];
        }
        if (isset($validated['title'])) {
            $updateData['title'] = $validated['title'];
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['amount'])) {
            $updateData['amount'] = $validated['amount'];
        }
        if (isset($validated['expense_date'])) {
            $updateData['expense_date'] = $validated['expense_date'];
        }
        if (isset($validated['is_active'])) {
            $updateData['is_active'] = $validated['is_active'];
        }
        if (isset($validated['recurring_expense_id'])) {
            $updateData['recurring_expense_id'] = $validated['recurring_expense_id'];
        }

        $expense->update($updateData);

        return response()->json($expense->load('category'));
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return response()->json(null, 204);
    }
}
