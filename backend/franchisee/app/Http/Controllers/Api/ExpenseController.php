<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\ExpenseServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID ExpenseController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on ExpenseServiceInterface abstraction
 */
class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseServiceInterface $expenseService
    ) {}

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'category_id' => $request->get('category_id'),
        ];

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $paginator = $this->expenseService->listExpenses($filters, $perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->expenseService->createExpense($request->expenseData());

        return response()->json($expense, 201);
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense): JsonResponse
    {
        return response()->json($expense->load('category'));
    }

    /**
     * Update the specified expense.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $expense = $this->expenseService->updateExpense($expense, $request->expenseData());

        return response()->json($expense);
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $this->expenseService->deleteExpense($expense);

        return response()->json(null, 204);
    }

    /**
     * Get audit history for an expense.
     */
    public function getHistory(Expense $expense): JsonResponse
    {
        $history = $this->expenseService->getExpenseHistory($expense);

        return response()->json($history);
    }
}
