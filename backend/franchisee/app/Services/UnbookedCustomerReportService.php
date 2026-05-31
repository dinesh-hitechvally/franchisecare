<?php

namespace App\Services;

use App\Contracts\Services\UnbookedCustomerReportServiceInterface;
use App\Models\Customer;
use App\Models\User;

class UnbookedCustomerReportService implements UnbookedCustomerReportServiceInterface
{
    public function index(User $user, array $filters): array
    {
        $from = $filters['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $user->company_id;
        $today = now()->toDateString();

        $query = Customer::query()
            ->withCount('pets')
            ->withCount('bookings')
            ->withCount([
                'bookings as future_bookings_count' => function ($bookingQuery) use ($today) {
                    $bookingQuery->whereDate('start_date', '>', $today);
                },
            ])
            ->withMax([
                'bookings as last_booked_date' => function ($bookingQuery) use ($today) {
                    $bookingQuery->whereDate('start_date', '<=', $today);
                },
            ], 'start_date')
            ->where('is_archived', false)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->orderBy('created_at', 'desc');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('id', (int) $filters['customer_id']);
        }

        if (isset($filters['number_of_pets'])) {
            $query->has('pets', '=', (int) $filters['number_of_pets']);
        }

        if (!empty($filters['phone'])) {
            $phone = trim((string) $filters['phone']);
            $query->where(function ($phoneQuery) use ($phone) {
                $phoneQuery
                    ->where('phone', 'like', "%{$phone}%")
                    ->orWhere('other_phone', 'like', "%{$phone}%");
            });
        }

        if (!empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (isset($filters['min'])) {
            $query->has('bookings', '>=', (int) $filters['min']);
        }

        if (isset($filters['max'])) {
            $query->has('bookings', '<=', (int) $filters['max']);
        }

        $customers = $query->get();

        $rows = $customers->map(function (Customer $customer) {
            $fullName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $address = trim((string) ($customer->address ?? ''));

            return [
                'id' => (string) $customer->id,
                'name' => $fullName !== '' ? $fullName : '-',
                'email' => $customer->email ?: '-',
                'mobile' => $customer->phone ?: ($customer->other_phone ?: '-'),
                'address' => $address !== '' ? $address : '-',
                'total_bookings' => (int) ($customer->bookings_count ?? 0),
                'future_bookings' => (int) ($customer->future_bookings_count ?? 0),
                'registered_date' => optional($customer->created_at)?->toDateTimeString(),
                'last_booked_date' => $customer->last_booked_date,
            ];
        })->values();

        return [
            'success' => true,
            'data' => $rows,
            'summary' => [
                'total_customers' => $customers->count(),
            ],
            'message' => 'Unbooked customer report generated successfully.',
        ];
    }
}
