<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\FranchisePayment;
use App\Models\SupportTicket;
use App\Models\FranchiseUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics()
    {
        $totalFranchises = Franchise::count();
        $activeFranchises = Franchise::where('status', 'ACTIVE')->count();
        $totalUsers = FranchiseUser::count();
        $openTickets = SupportTicket::where('status', '!=', 'RESOLVED')->count();

        // Revenue this month
        $monthlyRevenue = FranchisePayment::where('status', 'PAID')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('amount');

        // Revenue comparison with last month
        $lastMonthRevenue = FranchisePayment::where('status', 'PAID')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->sum('amount');

        $revenueChange = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        return response()->json([
            'total_franchises' => $totalFranchises,
            'active_franchises' => $activeFranchises,
            'total_users' => $totalUsers,
            'open_tickets' => $openTickets,
            'monthly_revenue' => $monthlyRevenue,
            'revenue_change' => $revenueChange,
        ]);
    }

    public function revenueChart(Request $request)
    {
        $months = $request->get('months', 12);
        
        $data = FranchisePayment::select(
                DB::raw('DATE_FORMAT(payment_date, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'PAID')
            ->where('payment_date', '>=', now()->subMonths($months))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json($data);
    }

    public function franchisesByStatus()
    {
        $data = Franchise::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        return response()->json($data);
    }

    public function recentActivities()
    {
        // Get recent franchise registrations
        $franchises = Franchise::latest()
            ->take(5)
            ->get(['id', 'name', 'created_at'])
            ->map(fn($f) => [
                'type' => 'franchise_created',
                'message' => "New franchise '{$f->name}' registered",
                'date' => $f->created_at,
            ]);

        // Get recent tickets
        $tickets = SupportTicket::with('franchise:id,name')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'type' => 'ticket_created',
                'message' => "Support ticket: {$t->title}",
                'date' => $t->created_at,
            ]);

        $activities = $franchises->concat($tickets)
            ->sortByDesc('date')
            ->take(10)
            ->values();

        return response()->json($activities);
    }

    public function topFranchises()
    {
        $franchises = Franchise::select('franchises.*')
            ->withSum(['payments as total_revenue' => function($q) {
                $q->where('status', 'PAID');
            }], 'amount')
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        return response()->json($franchises);
    }
}
