<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\BookingReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingReportController extends Controller
{
    public function __construct(
        protected BookingReportServiceInterface $bookingReportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();

        return response()->json($this->bookingReportService->index($request->user(), $from, $to));
    }
}
