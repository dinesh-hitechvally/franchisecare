<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\IncomeServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Income\StoreIncomeRequest;
use App\Http\Requests\Income\UpdateIncomeRequest;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID IncomeController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on IncomeServiceInterface abstraction
 */
class IncomeController extends Controller
{
    public function __construct(
        private IncomeServiceInterface $incomeService
    ) {}

    /**
     * Display a listing of incomes.
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

        $paginator = $this->incomeService->listIncomes($filters, $perPage);

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
     * Store a newly created income.
     */
    public function store(StoreIncomeRequest $request): JsonResponse
    {
        $income = $this->incomeService->createIncome($request->incomeData());

        return response()->json($income, 201);
    }

    /**
     * Display the specified income.
     */
    public function show(Income $income): JsonResponse
    {
        return response()->json($income->load('category'));
    }

    /**
     * Update the specified income.
     */
    public function update(UpdateIncomeRequest $request, Income $income): JsonResponse
    {
        $income = $this->incomeService->updateIncome($income, $request->incomeData());

        return response()->json($income);
    }

    /**
     * Remove the specified income.
     */
    public function destroy(Income $income): JsonResponse
    {
        $this->incomeService->deleteIncome($income);

        return response()->json(null, 204);
    }

    /**
     * Get audit history for an income.
     */
    public function getHistory(Income $income): JsonResponse
    {
        $history = $this->incomeService->getIncomeHistory($income);

        return response()->json($history);
    }
}
