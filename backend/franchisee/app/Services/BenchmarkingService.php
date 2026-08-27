<?php

namespace App\Services;

use App\Contracts\Services\BenchmarkingServiceInterface;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;

class BenchmarkingService implements BenchmarkingServiceInterface
{
    public function getReport(User $user, array $filters): array
    {
        [$start, $end] = $this->resolveDateRange($filters);

        $companyId = $user->company_id;
        $companyState = null;

        if ($companyId) {
            $companyState = Company::query()
                ->whereKey($companyId)
                ->value('state');
        }

        $yourMetrics = $this->calculateMetrics($start, $end, function ($incomeQuery, $bookingQuery) use ($companyId) {
            if ($companyId) {
                $incomeQuery->where('company_id', $companyId);
                $bookingQuery->where('company_id', $companyId);
            }
        });

        $stateMetrics = $this->calculateMetrics($start, $end, function ($incomeQuery, $bookingQuery) use ($companyState) {
            if (!$companyState) {
                $incomeQuery->whereRaw('1 = 0');
                $bookingQuery->whereRaw('1 = 0');
                return;
            }

            $stateCompanyIds = Company::query()
                ->where('state', $companyState)
                ->pluck('id');

            $incomeQuery->whereIn('company_id', $stateCompanyIds);
            $bookingQuery->whereIn('company_id', $stateCompanyIds);
        });

        $nationalMetrics = $this->calculateMetrics($start, $end);

        $data = [
            $this->buildRow('Income', $yourMetrics['income_total'], $stateMetrics['income_total'], $nationalMetrics['income_total']),
            $this->buildRow('Average Hourly Rate', $yourMetrics['avg_hourly_rate'], $stateMetrics['avg_hourly_rate'], $nationalMetrics['avg_hourly_rate']),
            $this->buildRow('Average Worked Hours', $yourMetrics['avg_worked_hours'], $stateMetrics['avg_worked_hours'], $nationalMetrics['avg_worked_hours']),
            $this->buildRow('Average Groom Value', $yourMetrics['avg_groom_value'], $stateMetrics['avg_groom_value'], $nationalMetrics['avg_groom_value']),
            $this->buildRow('Average Wash Value', $yourMetrics['avg_wash_value'], $stateMetrics['avg_wash_value'], $nationalMetrics['avg_wash_value']),
            $this->buildRow('Average Duration (mins)', $yourMetrics['avg_duration'], $stateMetrics['avg_duration'], $nationalMetrics['avg_duration']),
            $this->buildRow('Average Hourly Income', $yourMetrics['avg_hourly_rate'], $stateMetrics['avg_hourly_rate'], $nationalMetrics['avg_hourly_rate']),
        ];

        $rank = $this->calculateRank($start, $end, $companyId, $yourMetrics['income_total']);

        return [
            'success' => true,
            'data' => $data,
            'rank' => $rank,
            'message' => 'Benchmarking report retrieved successfully.',
        ];
    }

    private function resolveDateRange(array $filters): array
    {
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $fromInput = $filters['date_from'] ?? $filters['date_to'];
            $toInput = $filters['date_to'] ?? $filters['date_from'];

            $startCarbon = Carbon::parse($fromInput)->startOfDay();
            $endCarbon = Carbon::parse($toInput)->endOfDay();

            if ($startCarbon->gt($endCarbon)) {
                [$startCarbon, $endCarbon] = [$endCarbon, $startCarbon];
            }

            return [$startCarbon->toDateString(), $endCarbon->toDateString()];
        }

        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);

        return [
            Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
            Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
        ];
    }

    private function calculateRank(string $start, string $end, ?int $companyId, float $yourIncome): string
    {
        if (!$companyId) {
            return '-';
        }

        $companyIncomes = Income::query()
            ->whereBetween('income_date', [$start, $end])
            ->whereNotNull('company_id')
            ->selectRaw('company_id, SUM(amount) as total_income')
            ->groupBy('company_id')
            ->orderByDesc('total_income')
            ->pluck('total_income', 'company_id');

        $higherCount = 0;
        foreach ($companyIncomes as $cId => $income) {
            if ($cId != $companyId && (float) $income > $yourIncome) {
                $higherCount++;
            }
        }

        $rank = $higherCount + 1;
        $totalCompanies = $companyIncomes->count();

        if (!isset($companyIncomes[$companyId])) {
            $totalCompanies++;
            $rank = $totalCompanies;
        }

        return $rank . ' of ' . $totalCompanies;
    }

    private function calculateMetrics(string $start, string $end, ?callable $scope = null): array
    {
        $incomeQuery = Income::query()->whereBetween('income_date', [$start, $end]);
        $bookingQuery = Booking::query()
            ->with(['details.service'])
            ->whereIn('status', ['ACTIVE', 'COMPLETED'])
            ->whereBetween('start_date', [$start, $end]);

        if ($scope) {
            $scope($incomeQuery, $bookingQuery);
        }

        $incomeTotal = (float) $incomeQuery->sum('amount');
        $bookings = $bookingQuery->get();
        $bookingCount = max(1, $bookings->count());

        $totalDurationMinutes = (float) $bookings->sum(function ($booking) {
            return (float) ($booking->duration ?? 0);
        });

        $workedHours = $totalDurationMinutes / 60;
        $avgHourlyRate = $workedHours > 0 ? $incomeTotal / $workedHours : 0;
        $avgDuration = $bookingCount > 0 ? $totalDurationMinutes / $bookingCount : 0;

        $totalGroomValue = 0.0;
        $groomCount = 0;
        $totalWashValue = 0.0;
        $washCount = 0;

        foreach ($bookings as $booking) {
            foreach ($booking->details ?? [] as $detail) {
                $serviceName = strtolower((string) ($detail->service->name ?? ''));
                $price = (float) ($detail->price ?? 0);

                if (str_contains($serviceName, 'groom')) {
                    $totalGroomValue += $price;
                    $groomCount++;
                }

                if (str_contains($serviceName, 'wash')) {
                    $totalWashValue += $price;
                    $washCount++;
                }
            }
        }

        return [
            'income_total' => $incomeTotal,
            'avg_hourly_rate' => $avgHourlyRate,
            'avg_worked_hours' => $workedHours,
            'avg_groom_value' => $groomCount > 0 ? $totalGroomValue / $groomCount : 0,
            'avg_wash_value' => $washCount > 0 ? $totalWashValue / $washCount : 0,
            'avg_duration' => $avgDuration,
        ];
    }

    private function buildRow(string $heading, float $yourValue, float $stateAverage, float $nationalAverage): array
    {
        $format = fn (float $value): string => number_format($value, 2, '.', '');

        $toComparison = function (float $yourValue, float $averageValue) use ($format): string {
            if ($averageValue <= 0.0) {
                return $yourValue <= 0.0 ? '0.00%' : '100.00%';
            }
            return $format((($yourValue - $averageValue) / $averageValue) * 100) . '%';
        };

        return [
            'heading' => $heading,
            'your_details' => $format($yourValue),
            'state_average' => $format($stateAverage),
            'national_average' => $format($nationalAverage),
            'state_comparison' => $toComparison($yourValue, $stateAverage),
            'national_comparison' => $toComparison($yourValue, $nationalAverage),
        ];
    }
}
