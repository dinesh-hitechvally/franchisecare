<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRecurring;
use Illuminate\Http\Request;

class BookingRecurringController extends Controller
{
    /**
     * Display a listing of recurring bookings.
     */
    public function index(Request $request)
    {
        $query = BookingRecurring::with(['customer', 'details.item', 'details.service', 'bookings']);

        // Filter by authenticated user's company_id
        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';

            $query->where(function ($q) use ($term) {
                $q->where('notes', 'like', $term);

                $q->orWhereHas('customer', function ($customerQuery) use ($term) {
                    $customerQuery->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$term]);
                });

                $q->orWhereHas('details.service', function ($serviceQuery) use ($term) {
                    $serviceQuery->where('name', 'like', $term);
                });
            });
        }

        // Only for active (or unscoped) lists — do not strip cancelled rows by repeat_until
        if ($request->boolean('hide_expired') && $request->get('status') !== 'cancelled') {
            $query->whereNotNull('repeat_until')
                ->whereDate('repeat_until', '>=', now()->toDateString());
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        if ($request->filled('page')) {
            $page = max(1, (int) $request->input('page'));
            $paginator = $query->latest()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a newly created recurring booking.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
            'services' => 'required|array',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
            'services.*.item_id' => 'required|exists:customer_items,id',
            'services.*.duration' => 'required|integer',
            'recurring' => 'required|array',
            'recurring.frequency' => 'required|integer|between:1,20',
            'recurring.repeat_day' => 'required|string',
            'recurring.repeat_time' => 'required|date_format:H:i',
            'recurring.repeat_until' => 'required|date',
            'recurring.auto_extend' => 'required|boolean',
        ]);

        // Calculate total and duration from services
        $total = collect($validated['services'])->sum('service_price');
        $duration = collect($validated['services'])->sum('duration');

        // Set company_id from authenticated user
        $validated['company_id'] = auth()->user()->company_id;
        $validated['frequency'] = $request->input('recurring.frequency');
        $validated['repeat_time'] = $request->input('recurring.repeat_time');
        $validated['repeat_day'] = $request->input('recurring.repeat_day');
        $validated['repeat_until'] = $request->input('recurring.repeat_until');
        $validated['auto_extend'] = $request->input('recurring.auto_extend', false);
        $validated['status'] = 'active';
        $validated['total'] = $total;
        $validated['duration'] = $duration;

        $recurring = BookingRecurring::create($validated);

        // Create recurring details from services array
        foreach ($validated['services'] as $serviceItem) {
            $recurring->details()->create([
                'company_id' => auth()->user()->company_id,
                'customer_id' => $validated['customer_id'],
                'item_id' => $serviceItem['item_id'],
                'service_id' => $serviceItem['service_id'],
                'price' => $serviceItem['service_price'],
                'duration' => $serviceItem['duration'],
            ]);
        }

        // Reload recurring with fresh data and generate individual bookings
        $recurring = $recurring->fresh(['details']);
        $this->generateBookingsFromRecurring($recurring);

        return response()->json($recurring->fresh(['customer', 'details.item', 'details.service', 'bookings']), 201);
    }

    /**
     * Display the specified recurring booking.
     */
    public function show(BookingRecurring $bookingRecurring)
    {
        return response()->json($bookingRecurring->load(['customer', 'details.item', 'details.service', 'bookings']));
    }

    /**
     * Update the specified recurring booking.
     */
    public function update(Request $request, BookingRecurring $bookingRecurring)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'start_date' => 'sometimes|date',
            'color' => 'nullable|string',
            'notes' => 'nullable|string',
            'services' => 'sometimes|array',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
            'services.*.item_id' => 'required_with:services|exists:customer_items,id',
            'services.*.duration' => 'required_with:services|integer',
            'recurring' => 'sometimes|array',
            'recurring.frequency' => 'sometimes|integer|between:1,20',
            'recurring.repeat_day' => 'sometimes|string',
            'recurring.repeat_time' => 'sometimes|date_format:H:i',
            'recurring.repeat_until' => 'nullable|date',
            'recurring.auto_extend' => 'boolean',
        ]);

        // Update recurring fields if provided
        if ($request->has('recurring.frequency')) {
            $validated['frequency'] = $request->input('recurring.frequency');
        }
        if ($request->has('recurring.repeat_time')) {
            $validated['repeat_time'] = $request->input('recurring.repeat_time');
        }
        if ($request->has('recurring.repeat_day')) {
            $validated['repeat_day'] = $request->input('recurring.repeat_day');
        }
        if ($request->has('recurring.repeat_until')) {
            $validated['repeat_until'] = $request->input('recurring.repeat_until');
        }
        if ($request->has('recurring.auto_extend')) {
            $validated['auto_extend'] = $request->input('recurring.auto_extend');
        }

        $bookingRecurring->update($validated);

        // Handle services update
        if (isset($validated['services'])) {
            $bookingRecurring->details()->delete();
            foreach ($validated['services'] as $serviceItem) {
                $bookingRecurring->details()->create([
                    'company_id' => auth()->user()->company_id,
                    'customer_id' => $validated['customer_id'] ?? $bookingRecurring->customer_id,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $serviceItem['service_id'],
                    'price' => $serviceItem['service_price'],
                    'duration' => $serviceItem['duration'],
                ]);
            }

            // Recalculate total and duration
            $total = collect($validated['services'])->sum('service_price');
            $duration = collect($validated['services'])->sum('duration');
            $bookingRecurring->update(['total' => $total, 'duration' => $duration]);
        }

        // Regenerate bookings if recurring settings changed
        if (isset($validated['frequency']) || isset($validated['repeat_day']) || isset($validated['repeat_time']) || isset($validated['repeat_until'])) {
            // Delete existing future bookings
            $bookingRecurring->bookings()->where('start_date', '>=', now()->format('Y-m-d'))->delete();
            // Regenerate bookings
            $this->generateBookingsFromRecurring($bookingRecurring->fresh());
        }

        return response()->json($bookingRecurring->fresh(['customer', 'details.item', 'details.service', 'bookings']));
    }

    /**
     * Cancel a recurring booking.
     */
    public function cancel(Request $request, BookingRecurring $bookingRecurring)
    {
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string',
        ]);

        $bookingRecurring->update([
            'status' => 'cancelled',
            'cancelled_date' => now(),
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        $today = now()->format('Y-m-d');
        $bookingRecurring->bookings()
            ->where('start_date', '>=', $today)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        return response()->json(
            $bookingRecurring->fresh()->load(['customer', 'details.item', 'details.service', 'bookings'])
        );
    }

    /**
     * Soft-delete the specified recurring booking.
     */
    public function destroy(BookingRecurring $bookingRecurring)
    {
        $today = now()->format('Y-m-d');
        $bookingRecurring->bookings()
            ->where('start_date', '>=', $today)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $bookingRecurring->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate individual bookings from a recurring booking pattern
     */
    private function generateBookingsFromRecurring(BookingRecurring $recurring): void
    {
        $startDate = new \DateTime($recurring->start_date);
        $untilDate = new \DateTime($recurring->repeat_until);
        $frequency = (int) $recurring->frequency;
        $repeatDay = $recurring->repeat_day;
        $repeatTime = $recurring->repeat_time;

        $currentDate = clone $startDate;

        // Adjust to repeat day if specified
        if (!empty($repeatDay)) {
            $dayMap = [
                'Sunday' => 0, 
                'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
                'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6
            ];
            $targetDay = $dayMap[$repeatDay] ?? $currentDate->format('w');
            $currentDay = (int) $currentDate->format('w');

            // If not already on the correct day, move to the next occurrence
            if ($currentDay != $targetDay) {
                $daysUntilTarget = ($targetDay - $currentDay + 7) % 7;
                if ($daysUntilTarget == 0) {
                    $daysUntilTarget = 7;
                }
                $currentDate->modify("+{$daysUntilTarget} days");
            }
        }

        while ($currentDate <= $untilDate) {
            // Check if booking already exists for this date
            $existingBooking = $recurring->bookings()
                ->where('start_date', $currentDate->format('Y-m-d'))
                ->first();

            if (!$existingBooking) {
                // Create booking for this date
                $booking = Booking::create([
                    'company_id' => $recurring->company_id,
                    'customer_id' => $recurring->customer_id,
                    'recurring_id' => $recurring->id,
                    'start_date' => $currentDate->format('Y-m-d'),
                    'start_time' => $repeatTime,
                    'calendar_color' => $recurring->color ?? '#4F46E5',
                    'status' => 'active',
                    'total' => $recurring->total,
                    'duration' => $recurring->duration,
                    'notes' => $recurring->notes,
                ]);

                // Create booking details from recurring details
                foreach ($recurring->details as $detail) {
                    $booking->details()->create([
                        'company_id' => $recurring->company_id,
                        'item_id' => $detail->item_id,
                        'service_id' => $detail->service_id,
                        'price' => $detail->price,
                        'duration' => $detail->duration,
                    ]);
                }
            }

            // Move to next occurrence based on frequency
            $currentDate->modify("+{$frequency} week");
        }
    }
}
