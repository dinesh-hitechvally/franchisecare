<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ServiceServiceInterface;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(
        private ServiceServiceInterface $serviceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = array_filter([
            'category' => $request->input('category'),
        ]);

        return response()->json($this->serviceService->listServices($filters));
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->serviceService->createService($request->validated());
        return response()->json($service, 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json($service);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service = $this->serviceService->updateService($service, $request->validated());
        return response()->json($service);
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->serviceService->deleteService($service);
        return response()->json(null, 204);
    }
}
