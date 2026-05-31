<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ServicePriceServiceInterface;
use App\Http\Requests\ServicePrice\UpdateAllServicePriceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicePriceController extends Controller
{
    public function __construct(
        protected ServicePriceServiceInterface $servicePriceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->servicePriceService->index());
    }

    public function updateAll(UpdateAllServicePriceRequest $request): JsonResponse
    {
        $servicePrices = $this->servicePriceService->updateAll($request->validated()['services']);
        return response()->json($servicePrices);
    }
}
