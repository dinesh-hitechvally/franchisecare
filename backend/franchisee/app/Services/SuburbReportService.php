<?php

namespace App\Services;

use App\Contracts\Services\SuburbReportServiceInterface;
use App\Models\BookingDetail;
use App\Models\User;

class SuburbReportService implements SuburbReportServiceInterface
{
    public function index(User $user, array $filters): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $user->company_id;

        $detailsQuery = BookingDetail::query()
            ->with([
                'booking:id,total,customer_id',
                'booking.customer:id,suburb',
            ])
            ->leftJoin('services', 'booking_details.service_id', '=', 'services.id')
            ->select('booking_details.*', 'services.name as service_name')
            ->whereHas('booking', function ($query) use ($from, $to, $companyId) {
                $query->whereBetween('start_date', [$from, $to]);
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            });

        if (!empty($filters['suburb'])) {
            $detailsQuery->whereHas('booking.customer', function ($query) use ($filters) {
                $query->where('suburb', $filters['suburb']);
            });
        }

        if (isset($filters['min'])) {
            $detailsQuery->where('price', '>=', (float) $filters['min']);
        }

        if (isset($filters['max'])) {
            $detailsQuery->where('price', '<=', (float) $filters['max']);
        }

        $details = $detailsQuery->get();
        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#14b8a6', '#6366f1', '#06b6d4'];

        $suburbSummary = $details
            ->groupBy(function ($detail) {
                $suburb = trim((string) ($detail->booking?->customer?->suburb ?? ''));
                return $suburb !== '' ? $suburb : 'Unknown Suburb';
            })
            ->values()
            ->map(function ($group, $index) use ($palette) {
                $count = $group->count();
                $totalAmount = (float) $group->sum('price');
                $name = (string) (trim((string) ($group->first()?->booking?->customer?->suburb ?? '')) ?: 'Unknown Suburb');

                return [
                    'name' => $name,
                    'value' => $count,
                    'amount' => round($totalAmount, 2),
                    'total_booking_amount' => round($totalAmount, 2),
                    'avg_ticket' => $count > 0 ? round($totalAmount / $count, 2) : 0,
                    'color' => $palette[$index % count($palette)],
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->all();

        return [
            'success' => true,
            'summary' => [
                'total_suburbs' => count($suburbSummary),
                'total_bookings' => $details->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $suburbSummary,
            'message' => 'Suburb report generated successfully.',
        ];
    }
}
