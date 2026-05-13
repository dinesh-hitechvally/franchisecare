<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecurringExpense;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringExpense::with('category')->latest('start_date');

        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
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
            'start_date'          => 'required|date',
            'frequency'           => 'required|in:daily,weekly,monthly,yearly',
            'auto_extend'         => 'boolean',
            'total'               => 'required|numeric|min:0',
            'notes'               => 'nullable|string',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['status'] = 'active';

        $recurringExpense = RecurringExpense::create($validated);

        return response()->json($recurringExpense->load('category'), 201);
    }

    public function show(RecurringExpense $recurringExpense)
    {
        return response()->json($recurringExpense->load('category'));
    }

    public function update(Request $request, RecurringExpense $recurringExpense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'nullable|exists:expense_categories,id',
            'start_date'          => 'sometimes|required|date',
            'frequency'           => 'sometimes|required|in:daily,weekly,monthly,yearly',
            'auto_extend'         => 'boolean',
            'total'               => 'sometimes|required|numeric|min:0',
            'notes'               => 'nullable|string',
            'status'              => 'sometimes|in:active,cancelled,completed',
        ]);

        $recurringExpense->update($validated);

        return response()->json($recurringExpense->load('category'));
    }

    public function destroy(RecurringExpense $recurringExpense)
    {
        $recurringExpense->delete();

        return response()->json(null, 204);
    }
}
