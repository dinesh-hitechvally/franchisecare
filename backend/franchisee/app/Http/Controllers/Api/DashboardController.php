<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\DashboardServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardServiceInterface $dashboardService
    ) {}

    public function metrics(Request $request): JsonResponse
    {
        return response()->json($this->dashboardService->getMetrics($request->user()));
    }

    public function activities(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->integer('limit', 10));
        return response()->json($this->dashboardService->getActivities($request->user(), $limit));
    }

    public function news(Request $request): JsonResponse
    {
        return response()->json($this->dashboardService->getNews($request->user()?->company_id));
    }

    public function bookingSchedule(Request $request): JsonResponse
    {
        $days = max(1, (int) $request->integer('days', 5));
        return response()->json($this->dashboardService->getBookingSchedule($request->user()?->company_id, $days));
    }

    public function forecast(Request $request): JsonResponse
    {
        $weeks = max(1, (int) $request->integer('weeks', 12));
        return response()->json($this->dashboardService->getForecast($request->user()?->company_id, $weeks));
    }
}
