<?php

namespace App\Services;

use App\Contracts\Services\IncomeReportServiceInterface;
use App\Models\Booking;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;

class IncomeReportService implements IncomeReportServiceInterface
{
    public function index(User $user, array $filters): array
    {
        [$from, $to] = $this->resolveDateRange($filters);
        $companyId = $user->company_id;
        $palette = ['#0f766e', '#14b8a6', '#f59e0b', '#f97316', '#3b82f6', '#8b5cf6', '#ef4444', '#84cc16', '#06b6d4', '#ec4899'];

        $incomes = Income::query()
            ->with([
                'category:id,name',
                'booking.customer:id,first_name,last_name',
            ])
            ->whereBetween('income_date', [$from, $to])
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when(!empty($filters['category_id']), fn($query) => $query->where('income_category_id', $filters['category_id']))
            ->when(isset($filters['min']), fn($query) => $query->where('amount', '>=', (float) $filters['min']))
            ->when(isset($filters['max']), fn($query) => $query->where('amount', '<=', (float) $filters['max']))
            ->orderBy('income_date')
            ->get();

        $totalIncome = round((float) $incomes->sum('amount'), 2);

        $categoryData = $incomes
            ->groupBy(fn($income) => trim((string) ($income->category?->name ?? '')) ?: 'Uncategorized')
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
            ->map(fn(array $row, int $index) => array_merge($row, ['color' => $palette[$index % count($palette)]]))
            ->all();

        $customerBookings = Booking::query()
            ->with('customer:id,first_name,last_name')
            ->whereBetween('start_date', [$from, $to])
            ->whereIn('status', ['active', 'completed'])
            ->whereNotNull('customer_id')
            ->when($companyId, fn($query) => $query->where('company_id', $companyId))
            ->when(isset($filters['min']), fn($query) => $query->where('total', '>=', (float) $filters['min']))
            ->when(isset($filters['max']), fn($query) => $query->where('total', '<=', (float) $filters['max']))
            ->orderBy('start_date')
            ->get();

        $topCustomerData = $customerBookings
            ->filter(fn($booking) => (bool) $booking->customer)
            ->groupBy(fn($booking) => (string) $booking->customer->id)
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
            ->map(fn(array $row, int $index) => array_merge($row, ['color' => $palette[$index % count($palette)]]))
            ->all();

        $dateRangeData = $this->buildWeeklyDateRangeData($incomes, $from, $to);

        return [
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
        ];
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

    private function resolveDateRange(array $filters): array
    {
        $fromInput = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $toInput = $filters['date_to'] ?? now()->endOfMonth()->toDateString();

        $from = Carbon::parse($fromInput)->startOfDay();
        $to = Carbon::parse($toInput)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }
}
