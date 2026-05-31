<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\RecurringExpenseServiceInterface;
use App\Http\Requests\RecurringExpense\StoreRecurringExpenseRequest;
use App\Http\Requests\RecurringExpense\UpdateRecurringExpenseRequest;
use App\Models\RecurringExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    public function __construct(
        protected RecurringExpenseServiceInterface $recurringExpenseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        return response()->json($this->recurringExpenseService->paginate($filters, $perPage));
    }

    public function store(StoreRecurringExpenseRequest $request): JsonResponse
    {
        $recurringExpense = $this->recurringExpenseService->create($request->validated());
        return response()->json($recurringExpense, 201);
    }

    public function show(RecurringExpense $recurringExpense): JsonResponse
    {
        return response()->json($recurringExpense->load('category'));
    }

    public function update(UpdateRecurringExpenseRequest $request, RecurringExpense $recurringExpense): JsonResponse
    {
        $recurringExpense = $this->recurringExpenseService->update($recurringExpense, $request->validated());
        return response()->json($recurringExpense);
    }

    public function destroy(RecurringExpense $recurringExpense): JsonResponse
    {
        $this->recurringExpenseService->delete($recurringExpense);
        return response()->json(null, 204);
    }
}
