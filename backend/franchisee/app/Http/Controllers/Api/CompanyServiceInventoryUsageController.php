<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CompanyServiceInventoryUsageServiceInterface;
use App\Http\Requests\CompanyServiceInventoryUsage\StoreCompanyServiceInventoryUsageRequest;
use App\Http\Requests\CompanyServiceInventoryUsage\UpdateCompanyServiceInventoryUsageRequest;
use App\Models\CompanyServiceInventoryUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyServiceInventoryUsageController extends Controller
{
    public function __construct(
        protected CompanyServiceInventoryUsageServiceInterface $companyServiceInventoryUsageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['service_id', 'search']);
        $perPage = $request->filled('page') 
            ? max(1, min((int) $request->input('per_page', 25), 100)) 
            : null;

        return response()->json($this->companyServiceInventoryUsageService->index($filters, $perPage));
    }

    public function store(StoreCompanyServiceInventoryUsageRequest $request): JsonResponse
    {
        $usage = $this->companyServiceInventoryUsageService->create($request->validated());
        return response()->json($usage, 201);
    }

    public function update(UpdateCompanyServiceInventoryUsageRequest $request, CompanyServiceInventoryUsage $companyServiceInventoryUsage): JsonResponse
    {
        $usage = $this->companyServiceInventoryUsageService->update($companyServiceInventoryUsage, $request->validated());
        return response()->json($usage);
    }

    public function destroy(CompanyServiceInventoryUsage $companyServiceInventoryUsage): JsonResponse
    {
        $this->companyServiceInventoryUsageService->delete($companyServiceInventoryUsage);
        return response()->json(null, 204);
    }
}
