<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CustomerReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $request->user()?->company_id;

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

        if (!empty($validated['customer_id'])) {
            $detailsQuery->whereHas('booking', function ($query) use ($validated) {
                $query->where('customer_id', $validated['customer_id']);
            });
        }

        if (isset($validated['min'])) {
            $detailsQuery->where('price', '>=', (float) $validated['min']);
        }

        if (isset($validated['max'])) {
            $detailsQuery->where('price', '<=', (float) $validated['max']);
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

        // Build detailed breakdown by customer and service
        $serviceByCustomer = $details
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
                        $count = $serviceGroup->count();
                        $totalAmount = (float) $serviceGroup->sum('price');

                        return [
                            'count' => $count,
                            'total_amount' => round($totalAmount, 2),
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
            ->values()
            ->all();

        $weeklyData = $this->getWeeklyData($from, $to, $companyId, $validated);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_customer_services' => (int) $details->pluck('booking_id')->unique()->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $customerSummary,
            'service_by_customer' => $serviceByCustomer,
            'weekly_data' => $weeklyData,
            'message' => 'Customer report generated successfully.',
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

            // Constrain week boundaries to original date range
            $constrainedStart = $weekStart->isBefore($originalFrom) ? $originalFrom : $weekStart;
            $constrainedEnd = $weekEnd->isAfter($originalTo) ? $originalTo : $weekEnd;

            $myQuery = BookingDetail::query()
                ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.start_date', [$constrainedStart, $constrainedEnd]);

            if ($companyId) {
                $myQuery->where('bookings.company_id', $companyId);
            }
            if (!empty($filters['customer_id'])) {
                $myQuery->where('bookings.customer_id', $filters['customer_id']);
            }
            if (isset($filters['min'])) {
                $myQuery->where('booking_details.price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $myQuery->where('booking_details.price', '<=', (float) $filters['max']);
            }

            $myDetails = (clone $myQuery)->get(['booking_details.booking_id', 'booking_details.price']);
            $myCustomerCount = (int) $myDetails->pluck('booking_id')->unique()->count();
            $myCustomerAmount = round((float) $myDetails->sum('price'), 2);

            $maxQuery = BookingDetail::query()
                ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->whereBetween('bookings.start_date', [$constrainedStart, $constrainedEnd]);

            if (!empty($filters['customer_id'])) {
                $maxQuery->where('bookings.customer_id', $filters['customer_id']);
            }
            if (isset($filters['min'])) {
                $maxQuery->where('booking_details.price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $maxQuery->where('booking_details.price', '<=', (float) $filters['max']);
            }

            $maxCompanyRows = (clone $maxQuery)->get(['bookings.company_id', 'booking_details.booking_id', 'booking_details.price']);

            $groupedByCompany = $maxCompanyRows->groupBy('company_id')->map(function ($companyRows) {
                return [
                    'count' => (int) $companyRows->pluck('booking_id')->unique()->count(),
                    'amount' => round((float) $companyRows->sum('price'), 2),
                ];
            });

            $maxCustomerCount = (int) $groupedByCompany->max('count');
            $maxCustomerAmount = round((float) $groupedByCompany->max('amount'), 2);

            $weeklyData[] = [
                'weekRange' => $constrainedStart->format('jS M') . ' - ' . $constrainedEnd->format('jS M'),
                'startDate' => $constrainedStart->format('Y-m-d'),
                'endDate' => $constrainedEnd->format('Y-m-d'),
                'myCustomerBookings' => $myCustomerCount,
                'maxCustomerBookings' => max($myCustomerCount, $maxCustomerCount),
                'myCustomerAmount' => $myCustomerAmount,
                'maxCustomerAmount' => max($myCustomerAmount, $maxCustomerAmount),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
