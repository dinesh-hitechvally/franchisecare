<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\IncomeCategoryServiceInterface;
use App\Http\Requests\IncomeCategory\StoreIncomeCategoryRequest;
use App\Http\Requests\IncomeCategory\UpdateIncomeCategoryRequest;
use App\Models\IncomeCategory;
use Illuminate\Http\JsonResponse;

class IncomeCategoryController extends Controller
{
    public function __construct(
        protected IncomeCategoryServiceInterface $incomeCategoryService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->incomeCategoryService->all());
    }

    public function store(StoreIncomeCategoryRequest $request): JsonResponse
    {
        $category = $this->incomeCategoryService->create($request->validated());
        return response()->json($category, 201);
    }

    public function show(IncomeCategory $incomeCategory): JsonResponse
    {
        return response()->json($incomeCategory->loadCount('incomes'));
    }

    public function update(UpdateIncomeCategoryRequest $request, IncomeCategory $incomeCategory): JsonResponse
    {
        $category = $this->incomeCategoryService->update($incomeCategory, $request->validated());
        return response()->json($category);
    }

    public function destroy(IncomeCategory $incomeCategory): JsonResponse
    {
        $this->incomeCategoryService->delete($incomeCategory);
        return response()->json(null, 204);
    }
}
