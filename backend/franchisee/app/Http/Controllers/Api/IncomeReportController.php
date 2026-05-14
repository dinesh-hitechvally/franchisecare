<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'category_id' => 'nullable',
            'min' => 'nullable|numeric',
            'max' => 'nullable|numeric',
        ]);

        [$from, $to] = $this->resolveDateRange($validated);
        $companyId = $request->user()?->company_id;
        $palette = ['#0f766e', '#14b8a6', '#f59e0b', '#f97316', '#3b82f6', '#8b5cf6', '#ef4444', '#84cc16', '#06b6d4', '#ec4899'];

        $incomes = Income::query()
            ->with([
                'category:id,name',
                'booking.customer:id,first_name,last_name',
            ])
            ->whereBetween('income_date', [$from, $to])
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(!empty($validated['category_id']), function ($query) use ($validated) {
                $query->where('income_category_id', $validated['category_id']);
            })
            ->when(isset($validated['min']), function ($query) use ($validated) {
                $query->where('amount', '>=', (float) $validated['min']);
            })
            ->when(isset($validated['max']), function ($query) use ($validated) {
                $query->where('amount', '<=', (float) $validated['max']);
            })
            ->orderBy('income_date')
            ->get();

        $totalIncome = round((float) $incomes->sum('amount'), 2);

        $categoryData = $incomes
            ->groupBy(function ($income) {
                return trim((string) ($income->category?->name ?? '')) ?: 'Uncategorized';
            })
            ->map(function ($group, $categoryName) use ($totalIncome) {
                $amount = round((float) $group->sum('amount'), 2);

                return [
                    'name' => $categoryName,
                    'amount' => $amount,
                    'income_count' => $group->count(),
                    'percentage' => $totalIncome > 0 ? round(($amount / $totalIncome) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->map(function (array $row, int $index) use ($palette) {
                $row['color'] = $palette[$index % count($palette)];

                return $row;
            })
            ->all();

        $customerBookings = Booking::query()
            ->with('customer:id,first_name,last_name')
            ->whereBetween('start_date', [$from, $to])
            ->whereIn('status', ['active', 'completed'])
            ->whereNotNull('customer_id')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->when(isset($validated['min']), function ($query) use ($validated) {
                $query->where('total', '>=', (float) $validated['min']);
            })
            ->when(isset($validated['max']), function ($query) use ($validated) {
                $query->where('total', '<=', (float) $validated['max']);
            })
            ->orderBy('start_date')
            ->get();

        $topCustomerData = $customerBookings
            ->filter(function ($booking) {
                return (bool) $booking->customer;
            })
            ->groupBy(function ($booking) {
                return (string) $booking->customer->id;
            })
            ->map(function ($group) {
                $customer = $group->first()?->customer;
                $name = trim(implode(' ', array_filter([
                    trim((string) ($customer?->first_name ?? '')),
                    trim((string) ($customer?->last_name ?? '')),
                ])));

                return [
                    'customer_id' => (string) ($customer?->id ?? ''),
                    'name' => $name !== '' ? $name : 'Unknown Customer',
                    'amount' => round((float) $group->sum('total'), 2),
                    'income_count' => $group->count(),
                ];
            })
            ->sortByDesc('amount')
            ->take(10)
            ->values()
            ->map(function (array $row, int $index) use ($palette) {
                $row['color'] = $palette[$index % count($palette)];

                return $row;
            })
            ->all();

        $dateRangeData = $this->buildWeeklyDateRangeData($incomes, $from, $to);

        return response()->json([
            'success' => true,
            'summary' => [
                'total_income' => $totalIncome,
                'income_count' => $incomes->count(),
                'category_count' => count($categoryData),
                'customer_count' => count($topCustomerData),
            ],
            'category_data' => $categoryData,
            'top_customer_data' => $topCustomerData,
            'date_range_data' => $dateRangeData,
            'message' => 'Income report generated successfully.',
        ]);
    }

    private function buildWeeklyDateRangeData($incomes, string $from, string $to): array
    {
        $originalFrom = Carbon::parse($from)->startOfDay();
        $originalTo = Carbon::parse($to)->endOfDay();
        $cursor = Carbon::parse($from)->startOfWeek()->startOfDay();
        $end = Carbon::parse($to)->endOfWeek()->endOfDay();
        $weeklyData = [];

        while ($cursor->lt($end)) {
            $weekStart = $cursor->clone()->startOfDay();
            $weekEnd = $cursor->clone()->addDays(6)->endOfDay();

            if ($weekEnd->isBefore($originalFrom) || $weekStart->isAfter($originalTo)) {
                $cursor->addWeek();
                continue;
            }

            $constrainedStart = $weekStart->isBefore($originalFrom) ? $originalFrom->clone() : $weekStart;
            $constrainedEnd = $weekEnd->isAfter($originalTo) ? $originalTo->clone() : $weekEnd;

            $bucket = $incomes->filter(function ($income) use ($constrainedStart, $constrainedEnd) {
                $incomeDate = Carbon::parse($income->income_date)->startOfDay();

                return $incomeDate->betweenIncluded($constrainedStart->clone()->startOfDay(), $constrainedEnd->clone()->endOfDay());
            });

            $weeklyData[] = [
                'date' => $constrainedStart->format('j M') . ' - ' . $constrainedEnd->format('j M'),
                'start_date' => $constrainedStart->toDateString(),
                'end_date' => $constrainedEnd->toDateString(),
                'amount' => round((float) $bucket->sum('amount'), 2),
                'income_count' => $bucket->count(),
            ];

            $cursor->addWeek();
        }

        return $weeklyData;
    }

    private function resolveDateRange(array $validated): array
    {
        $fromInput = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $toInput = $validated['date_to'] ?? now()->endOfMonth()->toDateString();

        $from = Carbon::parse($fromInput)->startOfDay();
        $to = Carbon::parse($toInput)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }
}