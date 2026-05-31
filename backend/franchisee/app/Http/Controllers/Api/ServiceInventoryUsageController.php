<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ServiceInventoryUsageServiceInterface;
use App\Http\Requests\ServiceInventoryUsage\StoreServiceInventoryUsageRequest;
use App\Http\Requests\ServiceInventoryUsage\UpdateServiceInventoryUsageRequest;
use App\Models\ServiceInventoryUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceInventoryUsageController extends Controller
{
    public function __construct(
        protected ServiceInventoryUsageServiceInterface $serviceInventoryUsageService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['service_id', 'search']);
        $perPage = $request->filled('page') 
            ? max(1, min((int) $request->input('per_page', 25), 100)) 
            : null;

        return response()->json($this->serviceInventoryUsageService->index($filters, $perPage));
    }

    public function store(StoreServiceInventoryUsageRequest $request): JsonResponse
    {
        $usage = $this->serviceInventoryUsageService->create($request->validated());
        return response()->json($usage, 201);
    }

    public function update(UpdateServiceInventoryUsageRequest $request, ServiceInventoryUsage $serviceInventoryUsage): JsonResponse
    {
        $usage = $this->serviceInventoryUsageService->update($serviceInventoryUsage, $request->validated());
        return response()->json($usage);
    }

    public function destroy(ServiceInventoryUsage $serviceInventoryUsage): JsonResponse
    {
        $this->serviceInventoryUsageService->delete($serviceInventoryUsage);
        return response()->json(null, 204);
    }

    public function history(Request $request, $serviceId): JsonResponse
    {
        return response()->json($this->serviceInventoryUsageService->getHistory((int) $serviceId));
    }
}