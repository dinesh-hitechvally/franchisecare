<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\RecurringIncomeServiceInterface;
use App\Http\Requests\RecurringIncome\StoreRecurringIncomeRequest;
use App\Http\Requests\RecurringIncome\UpdateRecurringIncomeRequest;
use App\Models\RecurringIncome;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringIncomeController extends Controller
{
    public function __construct(
        protected RecurringIncomeServiceInterface $recurringIncomeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        return response()->json($this->recurringIncomeService->paginate($filters, $perPage));
    }

    public function store(StoreRecurringIncomeRequest $request): JsonResponse
    {
        $recurringIncome = $this->recurringIncomeService->create($request->validated());
        return response()->json($recurringIncome, 201);
    }

    public function show(RecurringIncome $recurringIncome): JsonResponse
    {
        return response()->json($recurringIncome->load('category'));
    }

    public function update(UpdateRecurringIncomeRequest $request, RecurringIncome $recurringIncome): JsonResponse
    {
        $recurringIncome = $this->recurringIncomeService->update($recurringIncome, $request->validated());
        return response()->json($recurringIncome);
    }

    public function destroy(RecurringIncome $recurringIncome): JsonResponse
    {
        $this->recurringIncomeService->delete($recurringIncome);
        return response()->json(null, 204);
    }
}
