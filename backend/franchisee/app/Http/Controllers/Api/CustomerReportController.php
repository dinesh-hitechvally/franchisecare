<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CustomerReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerReportController extends Controller
{
    public function __construct(
        protected CustomerReportServiceInterface $customerReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        return response()->json($this->customerReportService->index($request->user(), $validated));
    }
}
