<?php

namespace App\Services;

use App\Contracts\Services\CustomerReportServiceInterface;
use App\Models\BookingDetail;
use App\Models\User;
use Carbon\Carbon;

class CustomerReportService implements CustomerReportServiceInterface
{
    public function index(User $user, array $filters): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $user->company_id;

        $detailsQuery = BookingDetail::query()
            ->with([
                'booking:id,total,customer_id',
                'booking.customer:id,first_name,last_name',
            ])
            ->leftJoin('services', 'booking_details.service_id', '=', 'services.id')
            ->select('booking_details.*', 'services.name as service_name')
            ->whereHas('booking', function ($query) use ($from, $to, $companyId) {
                $query->whereBetween('start_date', [$from, $to]);
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            });

        if (!empty($filters['customer_id'])) {
            $detailsQuery->whereHas('booking', function ($query) use ($filters) {
                $query->where('customer_id', $filters['customer_id']);
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

        $customerSummary = $details
            ->groupBy(function ($detail) {
                $customer = $detail->booking?->customer;
                $customerName = trim((string) ($customer?->first_name ?? '')) . ' ' . trim((string) ($customer?->last_name ?? ''));
                return trim($customerName) !== '' ? $customerName : 'Unknown Customer';
            })
            ->values()
            ->map(function ($group, $index) use ($palette) {
                $count = $group->pluck('booking_id')->unique()->count();
                $totalAmount = (float) $group->sum('price');
                $customer = $group->first()?->booking?->customer;
                $customerName = trim((string) ($customer?->first_name ?? '')) . ' ' . trim((string) ($customer?->last_name ?? ''));
                $name = (string) (trim($customerName) ?: 'Unknown Customer');

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

        $serviceByCustomer = $this->buildServiceByCustomer($details);

        return [
            'success' => true,
            'summary' => [
                'total_customers' => count($customerSummary),
                'total_bookings' => $details->pluck('booking_id')->unique()->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $customerSummary,
            'service_by_customer' => $serviceByCustomer,
            'message' => 'Customer report generated successfully.',
        ];
    }

    private function buildServiceByCustomer($details): array
    {
        return $details
            ->groupBy(function ($detail) {
                $customer = $detail->booking?->customer;
                $customerName = trim((string) ($customer?->first_name ?? '')) . ' ' . trim((string) ($customer?->last_name ?? ''));
                return trim($customerName) !== '' ? $customerName : 'Unknown Customer';
            })
            ->map(function ($customerGroup) {
                return $customerGroup
                    ->groupBy(function ($detail) {
                        $serviceName = trim((string) ($detail->service_name ?? ''));
                        return $serviceName !== '' ? $serviceName : 'Unknown Service';
                    })
                    ->map(function ($serviceGroup) {
                        return [
                            'count' => $serviceGroup->count(),
                            'total_amount' => round((float) $serviceGroup->sum('price'), 2),
                        ];
                    })
                    ->all();
            })
            ->map(function ($services, $customer) {
                $result = [];
                foreach ($services as $serviceName => $data) {
                    $result[] = [
                        'customer_name' => $customer,
                        'service_name' => $serviceName,
                        'count' => $data['count'],
                        'total_amount' => $data['total_amount'],
                    ];
                }
                return $result;
            })
            ->flatten(1)
            ->all();
    }
}
