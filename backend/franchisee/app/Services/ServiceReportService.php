<?php

namespace App\Services;

use App\Contracts\Services\ServiceReportServiceInterface;
use App\Models\BookingDetail;
use App\Models\User;
use Carbon\Carbon;

class ServiceReportService implements ServiceReportServiceInterface
{
    public function index(User $user, array $filters): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $user->company_id;

        $detailsQuery = BookingDetail::query()
            ->with(['service:id,name', 'booking:id,total'])
            ->whereHas('booking', function ($query) use ($from, $to, $companyId) {
                $query->whereBetween('start_date', [$from, $to]);
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            });

        if (!empty($filters['service_id'])) {
            $detailsQuery->where('service_id', $filters['service_id']);
        }

        if (isset($filters['min'])) {
            $detailsQuery->where('price', '>=', (float) $filters['min']);
        }

        if (isset($filters['max'])) {
            $detailsQuery->where('price', '<=', (float) $filters['max']);
        }

        $details = $detailsQuery->get();
        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#6366f1', '#06b6d4'];

        $serviceSummary = $details
            ->groupBy('service_id')
            ->map(function ($group, $serviceId) use ($palette) {
                $count = $group->count();
                $lineAmount = (float) $group->sum('price');
                $totalBookingAmount = (float) $group->sum(fn($detail) => (float) ($detail->booking->total ?? 0));
                $name = (string) ($group->first()?->service?->name ?? 'Unknown Service');
                $color = $palette[((int) $serviceId) % count($palette)];

                return [
                    'service_id' => (int) $serviceId,
                    'name' => $name,
                    'value' => $count,
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

        $weeklyData = $this->getWeeklyData($from, $to, $companyId, $filters);

        return [
            'success' => true,
            'summary' => [
                'total_services' => (int) $details->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $serviceSummary,
            'weekly_data' => $weeklyData,
            'message' => 'Service report generated successfully.',
        ];
    }

    private function getWeeklyData(string $from, string $to, ?int $companyId, array $filters): array
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

            $detailsQuery = BookingDetail::query()
                ->whereHas('booking', function ($query) use ($weekStart, $weekEnd, $companyId) {
                    $query->whereBetween('start_date', [$weekStart, $weekEnd]);
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                });

            if (!empty($filters['service_id'])) {
                $detailsQuery->where('service_id', $filters['service_id']);
            }

            $details = $detailsQuery->get();

            $weeklyData[] = [
                'weekRange' => $weekStart->format('jS M') . ' - ' . $weekEnd->format('jS M'),
                'startDate' => $weekStart->format('Y-m-d'),
                'endDate' => $weekEnd->format('Y-m-d'),
                'services' => $details->count(),
                'revenue' => round((float) $details->sum('price'), 2),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
