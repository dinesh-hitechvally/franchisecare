<?php

namespace App\Services;

use App\Contracts\Services\BookingReportServiceInterface;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class BookingReportService implements BookingReportServiceInterface
{
    public function index(User $user, string $dateFrom, string $dateTo): array
    {
        $companyId = $user->company_id;

        $query = Booking::query()
            ->whereBetween('start_date', [$dateFrom, $dateTo]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $bookings = $query->get();

        $summary = [
            'total_bookings' => $bookings->count(),
            'completed' => $bookings->where('status', 'COMPLETED')->count(),
            'cancelled' => $bookings->where('status', 'CANCELLED')->count(),
            'no_show' => $bookings->where('status', 'no_show')->count(),
            'revenue' => $bookings->sum('total'),
        ];

        $weeklyReport = $this->getWeeklyData($dateFrom, $dateTo, $companyId);

        return [
            'success' => true,
            'summary' => $summary,
            'data' => $weeklyReport,
            'message' => 'Booking report generated successfully.'
        ];
    }

    private function getWeeklyData(string $from, string $to, ?int $companyId): array
    {
        $originalFrom = Carbon::parse($from)->startOfDay();
        $originalTo = Carbon::parse($to)->endOfDay();
        $fromCarbon = Carbon::parse($from)->startOfWeek();
        $toCarbon = Carbon::parse($to)->endOfWeek();

        $weeklyData = [];

        while ($fromCarbon < $toCarbon) {
            $weekEnd = $fromCarbon->clone()->addDays(6)->endOfDay();
            $weekStart = $fromCarbon->clone()->startOfDay();

            if ($weekEnd->isBefore($originalFrom) || $weekStart->isAfter($originalTo)) {
                $fromCarbon->addWeek();
                continue;
            }

            $myQuery = Booking::query()
                ->whereBetween('start_date', [$weekStart, $weekEnd]);
            if ($companyId) {
                $myQuery->where('company_id', $companyId);
            }
            $myBookings = $myQuery->get();

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
                'completed' => $myBookings->where('status', 'COMPLETED')->count(),
                'cancelled' => $myBookings->where('status', 'CANCELLED')->count(),
                'pending' => $myBookings->where('status', 'pending')->count(),
                'noShow' => $myBookings->where('status', 'no_show')->count(),
                'revenue' => $myBookings->sum('total'),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
