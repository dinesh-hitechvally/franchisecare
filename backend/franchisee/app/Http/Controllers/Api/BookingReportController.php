<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();

        $companyId = $request->user()?->company_id;
        $query = Booking::query()
            ->whereBetween('start_date', [$from, $to]);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $bookings = $query->get();

        $summary = [
            'total_bookings' => $bookings->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'no_show' => $bookings->where('status', 'no_show')->count(),
            'revenue' => $bookings->sum('total'),
        ];

        // Weekly aggregation for chart
        $weeklyReport = $this->getWeeklyData($from, $to, $companyId);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data' => $weeklyReport,
            'message' => 'Booking report generated successfully.'
        ]);
    }

    private function getWeeklyData(string $from, string $to, ?string $companyId): array
    {
        $originalFrom = Carbon::parse($from)->startOfDay();
        $originalTo = Carbon::parse($to)->endOfDay();
        $fromCarbon = Carbon::parse($from)->startOfWeek();
        $toCarbon = Carbon::parse($to)->endOfWeek();

        $weeklyData = [];

        while ($fromCarbon < $toCarbon) {
            $weekEnd = $fromCarbon->clone()->addDays(6)->endOfDay();
            $weekStart = $fromCarbon->clone()->startOfDay();

            // Only include weeks that overlap with the original date range
            if ($weekEnd->isBefore($originalFrom) || $weekStart->isAfter($originalTo)) {
                $fromCarbon->addWeek();
                continue;
            }

            // My Bookings (current company)
            $myQuery = Booking::query()
                ->whereBetween('start_date', [$weekStart, $weekEnd]);
            if ($companyId) {
                $myQuery->where('company_id', $companyId);
            }
            $myBookings = $myQuery->get();

            // Max Bookings (highest from any company)
            $maxQuery = Booking::query()
                ->whereBetween('start_date', [$weekStart, $weekEnd])
                ->selectRaw('company_id, COUNT(*) as booking_count')
                ->groupBy('company_id')
                ->orderByRaw('COUNT(*) DESC')
                ->first();
            $maxBookingsCount = $maxQuery ? $maxQuery->booking_count : 0;

            $weeklyData[] = [
                'weekRange' => $weekStart->format('jS M') . ' - ' . $weekEnd->format('jS M'),
                'startDate' => $weekStart->format('Y-m-d'),
                'endDate' => $weekEnd->format('Y-m-d'),
                'myBookings' => $myBookings->count(),
                'maxBookings' => max($myBookings->count(), $maxBookingsCount),
                'completed' => $myBookings->where('status', 'completed')->count(),
                'cancelled' => $myBookings->where('status', 'cancelled')->count(),
                'pending' => $myBookings->where('status', 'pending')->count(),
                'noShow' => $myBookings->where('status', 'no_show')->count(),
                'revenue' => $myBookings->sum('total'),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
