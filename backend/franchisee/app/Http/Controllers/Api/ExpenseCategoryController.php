<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ExpenseCategoryServiceInterface;
use App\Http\Requests\ExpenseCategory\StoreExpenseCategoryRequest;
use App\Http\Requests\ExpenseCategory\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        protected ExpenseCategoryServiceInterface $expenseCategoryService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->expenseCategoryService->all());
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $category = $this->expenseCategoryService->create($request->validated());
        return response()->json($category, 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        return response()->json($expenseCategory->loadCount('expenses'));
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $category = $this->expenseCategoryService->update($expenseCategory, $request->validated());
        return response()->json($category);
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->expenseCategoryService->delete($expenseCategory);
        return response()->json(null, 204);
    }
}
