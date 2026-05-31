<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\IncomeReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeReportController extends Controller
{
    public function __construct(
        protected IncomeReportServiceInterface $incomeReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'category_id' => 'nullable',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        return response()->json($this->incomeReportService->index($request->user(), $validated));
    }
}