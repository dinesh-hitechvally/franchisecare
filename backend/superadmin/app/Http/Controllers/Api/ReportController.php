<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\FranchisePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function franchisePerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $franchises = Franchise::select('franchises.*')
            ->withSum(['payments as total_revenue' => function($q) use ($startDate, $endDate) {
                $q->where('status', 'paid')
                  ->whereBetween('payment_date', [$startDate, $endDate]);
            }], 'amount')
            ->withCount(['users as active_users' => function($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('total_revenue')
            ->get();

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'franchises' => $franchises,
        ]);
    }

    public function revenueReport(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:' . (now()->year + 1),
            'franchise_id' => 'nullable|exists:franchises,id',
        ]);

        $year = $request->get('year', now()->year);

        $query = FranchisePayment::select(
                DB::raw('MONTH(payment_date) as month'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'paid')
            ->whereYear('payment_date', $year);

        if ($franchiseId = $request->get('franchise_id')) {
            $query->where('franchise_id', $franchiseId);
        }

        $data = $query->groupBy('month')->orderBy('month')->get();

        // Fill in missing months with zero
        $monthlyData = collect(range(1, 12))->map(function($month) use ($data) {
            $found = $data->firstWhere('month', $month);
            return [
                'month' => $month,
                'total' => $found ? $found->total : 0,
                'count' => $found ? $found->count : 0,
            ];
        });

        return response()->json([
            'year' => $year,
            'data' => $monthlyData,
            'summary' => [
                'total' => $monthlyData->sum('total'),
                'count' => $monthlyData->sum('count'),
            ],
        ]);
    }

    public function franchiseGrowth(Request $request)
    {
        $months = $request->get('months', 12);

        $data = Franchise::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as new_franchises')
            )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Calculate cumulative growth
        $cumulative = 0;
        $data = $data->map(function($item) use (&$cumulative) {
            $cumulative += $item->new_franchises;
            $item->cumulative = $cumulative;
            return $item;
        });

        return response()->json($data);
    }

    public function paymentStatus(Request $request)
    {
        $franchiseId = $request->get('franchise_id');

        $query = FranchisePayment::query();

        if ($franchiseId) {
            $query->where('franchise_id', $franchiseId);
        }

        $stats = [
            'paid' => (clone $query)->where('status', 'paid')->sum('amount'),
            'pending' => (clone $query)->where('status', 'pending')->sum('amount'),
            'overdue' => (clone $query)->where('status', 'pending')
                ->where('due_date', '<', now())->sum('amount'),
        ];

        $overduePayments = FranchisePayment::with('franchise:id,name')
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->take(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'overdue_payments' => $overduePayments,
        ]);
    }

    public function exportFranchises(Request $request)
    {
        $franchises = Franchise::all();

        // Return as JSON for now - can be extended to CSV/Excel
        return response()->json([
            'data' => $franchises,
            'generated_at' => now(),
        ]);
    }
}
