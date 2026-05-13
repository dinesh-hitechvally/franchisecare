<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SuburbReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'suburb' => 'nullable|string|max:255',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $request->user()?->company_id;

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

        if (!empty($validated['suburb'])) {
            $detailsQuery->whereHas('booking.customer', function ($query) use ($validated) {
                $query->where('suburb', $validated['suburb']);
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

        // Build detailed breakdown by suburb and service
        $serviceBySuburb = $details
            ->groupBy(function ($detail) {
                $suburb = trim((string) ($detail->booking?->customer?->suburb ?? ''));
                return $suburb !== '' ? $suburb : 'Unknown Suburb';
            })
            ->map(function ($suburbGroup) {
                return $suburbGroup
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
            ->map(function ($services, $suburb) {
                $result = [];
                foreach ($services as $serviceName => $data) {
                    $result[] = [
                        'suburb_name' => $suburb,
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
                'total_suburb_services' => (int) $details->count(),
                'total_revenue' => round((float) $details->sum('price'), 2),
            ],
            'data' => $suburbSummary,
            'service_by_suburb' => $serviceBySuburb,
            'weekly_data' => $weeklyData,
            'message' => 'Suburb report generated successfully.',
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
                ->leftJoin('customers', 'bookings.customer_id', '=', 'customers.id')
                ->whereBetween('bookings.start_date', [$constrainedStart, $constrainedEnd]);

            if ($companyId) {
                $myQuery->where('bookings.company_id', $companyId);
            }
            if (!empty($filters['suburb'])) {
                $myQuery->where('customers.suburb', $filters['suburb']);
            }
            if (isset($filters['min'])) {
                $myQuery->where('booking_details.price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $myQuery->where('booking_details.price', '<=', (float) $filters['max']);
            }

            $mySuburbCount = (int) $myQuery->count();

            $maxQuery = BookingDetail::query()
                ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')
                ->leftJoin('customers', 'bookings.customer_id', '=', 'customers.id')
                ->whereBetween('bookings.start_date', [$constrainedStart, $constrainedEnd]);

            if (!empty($filters['suburb'])) {
                $maxQuery->where('customers.suburb', $filters['suburb']);
            }
            if (isset($filters['min'])) {
                $maxQuery->where('booking_details.price', '>=', (float) $filters['min']);
            }
            if (isset($filters['max'])) {
                $maxQuery->where('booking_details.price', '<=', (float) $filters['max']);
            }

            $maxCompany = $maxQuery
                ->selectRaw('bookings.company_id, COUNT(*) as suburb_count')
                ->groupBy('bookings.company_id')
                ->orderByRaw('COUNT(*) DESC')
                ->first();

            $maxSuburbCount = $maxCompany ? (int) $maxCompany->suburb_count : 0;

            $weeklyData[] = [
                'weekRange' => $constrainedStart->format('jS M') . ' - ' . $constrainedEnd->format('jS M'),
                'startDate' => $constrainedStart->format('Y-m-d'),
                'endDate' => $constrainedEnd->format('Y-m-d'),
                'mySuburbServices' => $mySuburbCount,
                'maxSuburbServices' => max($mySuburbCount, $maxSuburbCount),
            ];

            $fromCarbon->addWeek();
        }

        return $weeklyData;
    }
}
