<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Income;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Tracking report - bookings and revenue trends
     */
    public function tracking(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $year = (int) $request->input('year', date('Y'));
        
        // Get monthly booking counts and revenue for the year
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            
            $bookings = Booking::where('company_id', $companyId)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->get();
            
            $completedBookings = $bookings->where('status', 'completed')->count();
            $cancelledBookings = $bookings->where('status', 'cancelled')->count();
            $totalBookings = $bookings->count();
            $revenue = $bookings->where('status', 'completed')->sum('total');
            
            $monthlyData[] = [
                'month' => $startDate->format('M'),
                'month_number' => $month,
                'total_bookings' => $totalBookings,
                'completed_bookings' => $completedBookings,
                'cancelled_bookings' => $cancelledBookings,
                'revenue' => round($revenue, 2),
            ];
        }
        
        // Calculate totals
        $totals = [
            'total_bookings' => array_sum(array_column($monthlyData, 'total_bookings')),
            'completed_bookings' => array_sum(array_column($monthlyData, 'completed_bookings')),
            'cancelled_bookings' => array_sum(array_column($monthlyData, 'cancelled_bookings')),
            'total_revenue' => round(array_sum(array_column($monthlyData, 'revenue')), 2),
        ];
        
        return response()->json([
            'year' => $year,
            'monthly_data' => $monthlyData,
            'totals' => $totals,
        ]);
    }

    /**
     * GST Summary report
     */
    public function gstSummary(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->endOfMonth()->toDateString());
        
        // GST collected (from incomes)
        $incomes = Income::where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('category')
            ->get();
        
        $gstCollectedItems = [];
        $totalGstCollected = 0;
        
        foreach ($incomes as $income) {
            $isGstInclusive = $income->category?->gst_inclusive ?? false;
            if ($isGstInclusive) {
                $gst = round($income->amount / 11, 2); // GST = amount / 11 for inclusive
                $totalGstCollected += $gst;
                $gstCollectedItems[] = [
                    'description' => $income->description ?? 'Income',
                    'date' => $income->date,
                    'amount' => round($income->amount, 2),
                    'gst' => $gst,
                ];
            }
        }
        
        // GST paid (from expenses)
        $expenses = Expense::where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('category')
            ->get();
        
        $gstPaidItems = [];
        $totalGstPaid = 0;
        
        foreach ($expenses as $expense) {
            $isGstInclusive = $expense->category?->gst_inclusive ?? false;
            if ($isGstInclusive) {
                $gst = round($expense->amount / 11, 2);
                $totalGstPaid += $gst;
                $gstPaidItems[] = [
                    'description' => $expense->description ?? 'Expense',
                    'date' => $expense->date,
                    'amount' => round($expense->amount, 2),
                    'gst' => $gst,
                ];
            }
        }
        
        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'gst_collected' => [
                'items' => $gstCollectedItems,
                'total' => round($totalGstCollected, 2),
            ],
            'gst_paid' => [
                'items' => $gstPaidItems,
                'total' => round($totalGstPaid, 2),
            ],
            'net_gst' => round($totalGstCollected - $totalGstPaid, 2),
        ]);
    }

    /**
     * Profit & Loss report
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $dateFrom = $request->input('date_from', Carbon::now()->startOfYear()->toDateString());
        $dateTo = $request->input('date_to', Carbon::now()->endOfYear()->toDateString());
        
        // Sales/Income section
        $incomes = Income::where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('category')
            ->get();
        
        $salesByCategory = $incomes->groupBy(function ($income) {
            return $income->category?->name ?? 'Other Income';
        })->map(function ($items, $category) {
            return [
                'category' => $category,
                'amount' => round($items->sum('amount'), 2),
                'count' => $items->count(),
            ];
        })->values();
        
        $totalSales = round($incomes->sum('amount'), 2);
        
        // Expenses section
        $expenses = Expense::where('company_id', $companyId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('category')
            ->get();
        
        $expensesByCategory = $expenses->groupBy(function ($expense) {
            return $expense->category?->name ?? 'Other Expenses';
        })->map(function ($items, $category) {
            return [
                'category' => $category,
                'amount' => round($items->sum('amount'), 2),
                'count' => $items->count(),
            ];
        })->values();
        
        $totalExpenses = round($expenses->sum('amount'), 2);
        
        // Calculate profit
        $grossProfit = $totalSales;
        $netProfit = $totalSales - $totalExpenses;
        
        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'sales' => [
                'items' => $salesByCategory,
                'total' => $totalSales,
            ],
            'expenses' => [
                'items' => $expensesByCategory,
                'total' => $totalExpenses,
            ],
            'gross_profit' => round($grossProfit, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin' => $totalSales > 0 ? round(($netProfit / $totalSales) * 100, 1) : 0,
        ]);
    }
}
