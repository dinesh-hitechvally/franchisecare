<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ServiceReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceReportController extends Controller
{
    public function __construct(
        protected ServiceReportServiceInterface $serviceReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'service_id' => 'nullable|exists:services,id',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        return response()->json($this->serviceReportService->index($request->user(), $validated));
    }
}
