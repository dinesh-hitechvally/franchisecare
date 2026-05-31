<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\UnbookedCustomerReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnbookedCustomerReportController extends Controller
{
    public function __construct(
        protected UnbookedCustomerReportServiceInterface $unbookedCustomerReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer',
            'min' => 'nullable|integer|min:0',
            'max' => 'nullable|integer|min:0',
            'number_of_pets' => 'nullable|integer|min:0',
            'phone' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:20',
        ]);

        return response()->json($this->unbookedCustomerReportService->index($request->user(), $validated));
    }
}
