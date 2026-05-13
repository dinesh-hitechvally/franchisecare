<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ServiceReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'service_id' => 'nullable|exists:services,id',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $request->user()?->company_id;

        $detailsQuery = BookingDetail::query()
            ->with(['service:id,name', 'booking:id,total'])
            ->whereHas('booking', function ($query) use ($from, $to, $companyId) {
                $query->whereBetween('start_date', [$from, $to]);

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            });

        if (!empty($validated['service_id'])) {
            $detailsQuery->where('service_id', $validated['service_id']);
        }

        if (isset($validated['min'])) {
            $detailsQuery->where('price', '>=', (float) $validated['min']);
        }

        if (isset($validated['max'])) {
            $detailsQuery->where('price', '<=', (float) $validated['max']);
        }

        $details = $detailsQuery->get();

        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#6366f1', '#06b6d4'];

        $serviceSummary = $details
            ->groupBy('service_id')
            ->map(function ($group, $serviceId) use ($palette) {
                $count = $group->count();
                $lineAmount = (float) $group->sum('price');
                $totalBookingAmount = (float) $group->sum(function ($detail) {
                    return (float) ($detail->booking->total ?? 0);
                });
                $name = (string) ($group->first()?->service?->name ?? 'Unknown Service');
                $color = $palette[((int) $serviceId) % count($palette)];

                return [
                    'service_id' => (int) $serviceId,
                    'name' => $name,
                    'value' => $count,
                    // Keep `amount` for compatibility with current frontend chart/table usage.
                    'amount' => round($totalBookingAmount, 2),
                    'line_amount' => round($lineAmount, 2),
                    'total_booking_amount' => round($totalBookingAmount, 2),
                    'avg_ticket' => $count > 0 ? round($totalBookingAmount / $count, 2) : 0,
                    'color' => $color,
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        $weeklyData = $this->getWeeklyData($from, $to, $companyId, $validated);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_services' => (int) $details->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $serviceSummary,
            'weekly_data' => $weeklyData,
            'message' => 'Service report generated successfully.',
        ]);
    }

    private function getWeeklyData(string $from, string $to, ?string $companyId, array $filters): array
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

            $myQuery = BookingDetail::query()
                ->whereHas('booking', function ($query) use ($weekStart, $weekEnd, $companyId) {
                    $query->whereBetween('start_date', [$weekStart, $weekEnd]);
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                });

            if (!empty($filters['service_id'])) {
                $myQuery->where('service_id', $filters['service_id']);
            }
            if (isset($filters['min'])) {
                $myQuery->where('price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $myQuery->where('price', '<=', (float) $filters['max']);
            }

            $myServicesCount = $myQuery->count();

            $maxQuery = BookingDetail::query()
                ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.start_date', [$weekStart, $weekEnd]);

            if (!empty($filters['service_id'])) {
                $maxQuery->where('booking_details.service_id', $filters['service_id']);
            }
            if (isset($filters['min'])) {
                $maxQuery->where('booking_details.price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $maxQuery->where('booking_details.price', '<=', (float) $filters['max']);
            }

            $maxCompany = $maxQuery
                ->selectRaw('bookings.company_id, COUNT(*) as service_count')
                ->groupBy('bookings.company_id')
                ->orderByRaw('COUNT(*) DESC')
                ->first();

            $maxServicesCount = $maxCompany ? (int) $maxCompany->service_count : 0;

            $weeklyData[] = [
                'weekRange' => $weekStart->format('jS M') . ' - ' . $weekEnd->format('jS M'),
                'startDate' => $weekStart->format('Y-m-d'),
                'endDate' => $weekEnd->format('Y-m-d'),
                'myServices' => $myServicesCount,
                'maxServices' => max($myServicesCount, $maxServicesCount),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
