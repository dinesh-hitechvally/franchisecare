<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class UnbookedCustomerReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'customer_id' => 'nullable|integer',
            'min' => 'nullable|integer|min:0',
            'max' => 'nullable|integer|min:0',
            'number_of_pets' => 'nullable|integer|min:0',
            'phone' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:20',
        ]);

        $from = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $to = $validated['date_to'] ?? now()->endOfMonth()->toDateString();
        $companyId = $request->user()?->company_id;
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

        if (!empty($validated['customer_id'])) {
            $query->where('id', (int) $validated['customer_id']);
        }

        if (isset($validated['number_of_pets'])) {
            $query->has('pets', '=', (int) $validated['number_of_pets']);
        }

        if (!empty($validated['phone'])) {
            $phone = trim((string) $validated['phone']);
            $query->where(function ($phoneQuery) use ($phone) {
                $phoneQuery
                    ->where('phone', 'like', "%{$phone}%")
                    ->orWhere('other_phone', 'like', "%{$phone}%");
            });
        }

        if (!empty($validated['state'])) {
            $query->where('state', $validated['state']);
        }

        if (isset($validated['min'])) {
            $query->has('bookings', '>=', (int) $validated['min']);
        }

        if (isset($validated['max'])) {
            $query->has('bookings', '<=', (int) $validated['max']);
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

        return response()->json([
            'success' => true,
            'summary' => [
                'total_customers' => $rows->count(),
                'total_bookings' => (int) $rows->sum('total_bookings'),
                'total_future_bookings' => (int) $rows->sum('future_bookings'),
            ],
            'data' => $rows,
            'message' => 'Unbooked customer report generated successfully.',
        ]);
    }
}
