<?php

namespace App\Services;

use App\Contracts\Services\DashboardServiceInterface;
use App\Models\Booking;
use App\Models\ForumNotification;
use App\Models\News;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService implements DashboardServiceInterface
{
    public function getMetrics(User $user): array
    {
        $companyId = $user->company_id;
        $year = now()->year;
        $month = now()->month;

        $activeCustomers = DB::table('customers')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->where('is_archived', false)
            ->count();

        $activeBookingsCY = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereYear('start_date', $year)
            ->where('status', 'active')
            ->count();

        $cancellationsCY = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereYear('start_date', $year)
            ->where('status', 'cancelled')
            ->count();

        $attentionCount = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->count();

        $forumNotifications = ForumNotification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $birthdayThisMonth = 0;
        if (Schema::hasTable('customer_items')) {
            $birthdayThisMonth = DB::table('customer_items')
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereMonth('birth_date', $month)
                ->count();
        }

        $leadsCount = 0;
        if (Schema::hasTable('leads')) {
            $hasLeadsCompanyColumn = Schema::hasColumn('leads', 'company_id');
            $leadsCount = DB::table('leads')
                ->when($companyId && $hasLeadsCompanyColumn, fn ($q) => $q->where('company_id', $companyId))
                ->count();
        }

        $cancelRequests = 0;
        if (Schema::hasTable('booking_cancel_requests')) {
            $hasCancelRequestsCompanyColumn = Schema::hasColumn('booking_cancel_requests', 'company_id');
            $cancelRequests = DB::table('booking_cancel_requests')
                ->when($companyId && $hasCancelRequestsCompanyColumn, fn ($q) => $q->where('company_id', $companyId))
                ->count();
        }

        $operatorMessages = 0;
        if (Schema::hasTable('operator_messages')) {
            $hasOperatorMessagesCompanyColumn = Schema::hasColumn('operator_messages', 'company_id');
            $operatorMessages = DB::table('operator_messages')
                ->when($companyId && $hasOperatorMessagesCompanyColumn, fn ($q) => $q->where('company_id', $companyId))
                ->count();
        }

        $totalRevenue = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereYear('start_date', $year)
            ->whereIn('status', ['active', 'completed', 'archived'])
            ->sum('total');

        $thisMonthRevenue = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->whereIn('status', ['active', 'completed', 'archived'])
            ->sum('total');

        return [
            'activeCustomers' => (int) $activeCustomers,
            'activeBookings' => (int) $activeBookingsCY,
            'cancellations' => (int) $cancellationsCY,
            'forumNotifications' => (int) $forumNotifications,
            'birthdayThisMonth' => (int) $birthdayThisMonth,
            'leadsCount' => (int) $leadsCount,
            'cancelRequests' => (int) $cancelRequests,
            'operatorMessages' => (int) $operatorMessages,
            'attentionCount' => (int) $attentionCount,
            'totalRevenue' => (float) $totalRevenue,
            'thisMonthRevenue' => (float) $thisMonthRevenue,
        ];
    }

    public function getActivities(User $user, int $limit = 10): array
    {
        $companyId = $user->company_id;

        $bookingEvents = Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('customer:id,first_name,last_name')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(function (Booking $booking) {
                $customerName = trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''));
                $label = $customerName !== '' ? $customerName : ('Booking #' . $booking->id);

                return [
                    'id' => 'booking-' . $booking->id,
                    'message' => sprintf(
                        '%s booking is %s - %s',
                        $label,
                        ucfirst($booking->status),
                        Carbon::parse($booking->updated_at)->format('jS M Y h:i A')
                    ),
                    'createdAt' => Carbon::parse($booking->updated_at)->toISOString(),
                ];
            });

        $forumEvents = ForumNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (ForumNotification $notification) {
                return [
                    'id' => 'forum-' . $notification->id,
                    'message' => $notification->message,
                    'createdAt' => Carbon::parse($notification->created_at)->toISOString(),
                ];
            });

        return $bookingEvents
            ->concat($forumEvents)
            ->sortByDesc('createdAt')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function getNews(?int $companyId): array
    {
        $hasNewsCompanyColumn = Schema::hasColumn('news', 'company_id');

        $baseQuery = News::query()
            ->when($companyId && $hasNewsCompanyColumn, fn ($q) => $q->where('company_id', $companyId))
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        $news = (clone $baseQuery)
            ->limit(6)
            ->get();

        $bluesNews = (clone $baseQuery)
            ->where(function ($q) {
                $q->where('category', 'Blues News')
                    ->orWhere('title', 'like', '%Blues News%');
            })
            ->limit(6)
            ->get();

        return [
            'news' => $news,
            'bluesNews' => $bluesNews,
        ];
    }

    public function getBookingSchedule(?int $companyId, int $days = 5): array
    {
        $endDate = now()->copy()->addDays($days)->toDateString();

        return Booking::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('customer:id,first_name,last_name')
            ->whereBetween('start_date', [now()->toDateString(), $endDate])
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('start_date')
            ->orderBy('start_time')
            ->limit(100)
            ->get()
            ->map(function (Booking $booking) {
                $customerName = trim(($booking->customer?->first_name ?? '') . ' ' . ($booking->customer?->last_name ?? ''));

                return [
                    'id' => (string) $booking->id,
                    'customer' => $customerName !== '' ? $customerName : 'Unknown Customer',
                    'date' => Carbon::parse($booking->start_date)->toDateString(),
                    'startTime' => $booking->start_time,
                    'total' => (float) $booking->total,
                ];
            })
            ->toArray();
    }

    public function getForecast(?int $companyId, int $weeks = 12): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addWeeks($weeks)->subDay();

        $bookingSummary = DB::table('bookings')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['active', 'completed'])
            ->selectRaw('YEARWEEK(start_date, 3) as yw, COUNT(*) as bookings, COALESCE(SUM(total), 0) as income')
            ->groupBy('yw')
            ->get()
            ->keyBy('yw');

        $serviceSummary = DB::table('bookings')
            ->join('booking_details', 'booking_details.booking_id', '=', 'bookings.id')
            ->when($companyId, fn ($q) => $q->where('bookings.company_id', $companyId))
            ->whereBetween('bookings.start_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('bookings.status', ['active', 'completed'])
            ->selectRaw('YEARWEEK(bookings.start_date, 3) as yw, COUNT(booking_details.id) as services')
            ->groupBy('yw')
            ->get()
            ->keyBy('yw');

        $rows = [];
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = $start->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            $yw = (int) $weekStart->format('oW');

            $bookings = (int) ($bookingSummary[$yw]->bookings ?? 0);
            $income = (float) ($bookingSummary[$yw]->income ?? 0);
            $services = (int) ($serviceSummary[$yw]->services ?? 0);

            $rows[] = [
                'id' => (string) ($i + 1),
                'week' => $weekStart->format('j M') . ' - ' . $weekEnd->format('j M'),
                'bookings' => $bookings,
                'income' => $income,
                'services' => $services,
            ];
        }

        return $rows;
    }
}
