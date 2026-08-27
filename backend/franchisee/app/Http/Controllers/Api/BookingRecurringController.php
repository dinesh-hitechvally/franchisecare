<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\BookingRecurringServiceInterface;
use App\Http\Requests\BookingRecurring\StoreBookingRecurringRequest;
use App\Http\Requests\BookingRecurring\UpdateBookingRecurringRequest;
use App\Models\BookingRecurring;
use App\Models\BookingRecurringAudit;
use App\Models\BookingRecurringDetailAudit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingRecurringController extends Controller
{
    public function __construct(
        private BookingRecurringServiceInterface $bookingRecurringService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'company_id' => auth()->user()?->company_id,
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'search' => $request->input('search'),
            'hide_expired' => $request->boolean('hide_expired'),
        ];

        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        if ($request->filled('page')) {
            $result = $this->bookingRecurringService->listBookingRecurrings(array_filter($filters), $perPage);
            
            return response()->json([
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
            ]);
        }

        return response()->json($this->bookingRecurringService->listBookingRecurrings(array_filter($filters)));
    }

    public function store(Request $request): JsonResponse
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

        // Prepare data for service
        $data = [
            'company_id' => auth()->user()->company_id,
            'customer_id' => $validated['customer_id'],
            'start_date' => $validated['start_date'],
            'color' => $validated['color'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'frequency' => $request->input('recurring.frequency'),
            'repeat_time' => $request->input('recurring.repeat_time'),
            'repeat_day' => $request->input('recurring.repeat_day'),
            'repeat_until' => $request->input('recurring.repeat_until'),
            'auto_extend' => $request->input('recurring.auto_extend', false),
            'status' => 'ACTIVE',
            'total' => collect($validated['services'])->sum('service_price'),
            'duration' => collect($validated['services'])->sum('duration'),
            'details' => array_map(function ($service) use ($validated) {
                return [
                    'company_id' => auth()->user()->company_id,
                    'customer_id' => $validated['customer_id'],
                    'item_id' => $service['item_id'],
                    'service_id' => $service['service_id'],
                    'price' => $service['service_price'],
                    'duration' => $service['duration'],
                ];
            }, $validated['services']),
        ];

        $recurring = $this->bookingRecurringService->createBookingRecurring($data);

        return response()->json($recurring, 201);
    }

    public function show(BookingRecurring $bookingRecurring): JsonResponse
    {
        return response()->json($this->bookingRecurringService->getBookingRecurring($bookingRecurring->id));
    }

    public function update(Request $request, BookingRecurring $bookingRecurring): JsonResponse
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

        // Map recurring fields
        $data = $validated;
        if ($request->has('recurring.frequency')) {
            $data['frequency'] = $request->input('recurring.frequency');
        }
        if ($request->has('recurring.repeat_time')) {
            $data['repeat_time'] = $request->input('recurring.repeat_time');
        }
        if ($request->has('recurring.repeat_day')) {
            $data['repeat_day'] = $request->input('recurring.repeat_day');
        }
        if ($request->has('recurring.repeat_until')) {
            $data['repeat_until'] = $request->input('recurring.repeat_until');
        }
        if ($request->has('recurring.auto_extend')) {
            $data['auto_extend'] = $request->input('recurring.auto_extend');
        }

        // Map services to details
        if (isset($validated['services'])) {
            $data['total'] = collect($validated['services'])->sum('service_price');
            $data['duration'] = collect($validated['services'])->sum('duration');
            $data['details'] = array_map(function ($service) use ($validated, $bookingRecurring) {
                return [
                    'company_id' => auth()->user()->company_id,
                    'customer_id' => $validated['customer_id'] ?? $bookingRecurring->customer_id,
                    'item_id' => $service['item_id'],
                    'service_id' => $service['service_id'],
                    'price' => $service['service_price'],
                    'duration' => $service['duration'],
                ];
            }, $validated['services']);
        }

        unset($data['services'], $data['recurring']);

        $recurring = $this->bookingRecurringService->updateBookingRecurring($bookingRecurring, $data);

        return response()->json($recurring);
    }

    public function cancel(Request $request, BookingRecurring $bookingRecurring): JsonResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string',
        ]);

        $bookingRecurring->update([
            'status' => 'CANCELLED',
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

    public function destroy(BookingRecurring $bookingRecurring): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $bookingRecurring->bookings()
            ->where('start_date', '>=', $today)
            ->where('status', 'active')
            ->update(['status' => 'cancelled']);

        $this->bookingRecurringService->deleteBookingRecurring($bookingRecurring);

        return response()->json(null, 204);
    }

    public function getHistory(BookingRecurring $bookingRecurring): JsonResponse
    {
        $history = BookingRecurringAudit::where('booking_recurring_id', $bookingRecurring->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);

        $userIds = $history->getCollection()->pluck('performed_by')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'first_name', 'last_name'])->keyBy('id');

        $history->setCollection($history->getCollection()->map(function ($audit) use ($users) {
            $user = $users->get($audit->performed_by);
            if (!$user) {
                return $audit;
            }
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $audit->performed_by_name = $fullName !== '' ? $fullName : ($user->name ?? null);
            return $audit;
        }));

        return response()->json($history);
    }

    public function getDetailHistory(BookingRecurring $bookingRecurring): JsonResponse
    {
        $history = BookingRecurringDetailAudit::where('recurring_id', $bookingRecurring->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);

        $userIds = $history->getCollection()->pluck('performed_by')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'first_name', 'last_name'])->keyBy('id');

        $history->setCollection($history->getCollection()->map(function ($audit) use ($users) {
            $user = $users->get($audit->performed_by);
            if (!$user) {
                return $audit;
            }
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            $audit->performed_by_name = $fullName !== '' ? $fullName : ($user->name ?? null);
            return $audit;
        }));

        return response()->json($history);
    }
}
