<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\ReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        protected ReportServiceInterface $reportService
    ) {}

    public function tracking(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        return response()->json($this->reportService->tracking($request->user(), $year));
    }

    public function gstSummary(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->toDateString());
        return response()->json($this->reportService->gstSummary($request->user(), $dateFrom, $dateTo));
    }

    public function profitLoss(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->endOfYear()->toDateString());
        return response()->json($this->reportService->profitLoss($request->user(), $dateFrom, $dateTo));
    }
}
