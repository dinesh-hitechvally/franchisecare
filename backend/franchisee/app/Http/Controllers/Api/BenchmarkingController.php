<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Company;
use App\Models\Income;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BenchmarkingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        if (!empty($validated['date_from']) || !empty($validated['date_to'])) {
            $fromInput = $validated['date_from'] ?? $validated['date_to'];
            $toInput = $validated['date_to'] ?? $validated['date_from'];

            $startCarbon = Carbon::parse($fromInput)->startOfDay();
            $endCarbon = Carbon::parse($toInput)->endOfDay();

            if ($startCarbon->gt($endCarbon)) {
                [$startCarbon, $endCarbon] = [$endCarbon, $startCarbon];
            }

            $start = $startCarbon->toDateString();
            $end = $endCarbon->toDateString();
        } else {
            $year = (int) ($validated['year'] ?? now()->year);
            $month = (int) ($validated['month'] ?? now()->month);
            $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        }

        $companyId = $request->user()?->company_id;
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

        $format = static function (float $value): string {
            return number_format($value, 2, '.', '');
        };

        $toComparison = static function (float $yourValue, float $averageValue) use ($format): string {
            if ($averageValue <= 0.0) {
                return $yourValue <= 0.0 ? '0.00%' : '100.00%';
            }

            return $format((($yourValue - $averageValue) / $averageValue) * 100) . '%';
        };

        $buildRow = static function (string $heading, float $yourValue, float $stateAverage, float $nationalAverage) use ($format, $toComparison) {
            return [
                'heading' => $heading,
                'your_details' => $format($yourValue),
                'state_average' => $format($stateAverage),
                'national_average' => $format($nationalAverage),
                'state_comparison' => $toComparison($yourValue, $stateAverage),
                'national_comparison' => $toComparison($yourValue, $nationalAverage),
            ];
        };

        $data = [
            $buildRow('Income', $yourMetrics['income_total'], $stateMetrics['income_total'], $nationalMetrics['income_total']),
            $buildRow('Average Hourly Rate', $yourMetrics['avg_hourly_rate'], $stateMetrics['avg_hourly_rate'], $nationalMetrics['avg_hourly_rate']),
            $buildRow('Average Worked Hours', $yourMetrics['avg_worked_hours'], $stateMetrics['avg_worked_hours'], $nationalMetrics['avg_worked_hours']),
            $buildRow('Average Groom Value', $yourMetrics['avg_groom_value'], $stateMetrics['avg_groom_value'], $nationalMetrics['avg_groom_value']),
            $buildRow('Average Wash Value', $yourMetrics['avg_wash_value'], $stateMetrics['avg_wash_value'], $nationalMetrics['avg_wash_value']),
            $buildRow('Average Duration (mins)', $yourMetrics['avg_duration'], $stateMetrics['avg_duration'], $nationalMetrics['avg_duration']),
            $buildRow('Average Hourly Income', $yourMetrics['avg_hourly_rate'], $stateMetrics['avg_hourly_rate'], $nationalMetrics['avg_hourly_rate']),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
            'rank' => '-',
            'message' => 'Benchmarking report retrieved successfully.',
        ]);
    }

    private function calculateMetrics(string $start, string $end, ?callable $scope = null): array
    {
        $incomeQuery = Income::query()->whereBetween('income_date', [$start, $end]);
        $bookingQuery = Booking::query()
            ->with(['details.service'])
            ->whereIn('status', ['active', 'completed'])
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
            foreach ($booking->details as $detail) {
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

        $avgGroomValue = $groomCount > 0 ? $totalGroomValue / $groomCount : 0;
        $avgWashValue = $washCount > 0 ? $totalWashValue / $washCount : 0;

        $distinctDays = max(1, $bookings->pluck('start_date')->unique()->count());
        $avgWorkedHours = $workedHours > 0 ? $workedHours / $distinctDays : 0;

        return [
            'income_total' => $incomeTotal,
            'avg_hourly_rate' => $avgHourlyRate,
            'avg_worked_hours' => $avgWorkedHours,
            'avg_groom_value' => $avgGroomValue,
            'avg_wash_value' => $avgWashValue,
            'avg_duration' => $avgDuration,
        ];
    }
}
