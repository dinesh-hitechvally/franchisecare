<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\EmailTemplateHelper;
use App\Models\Booking;
use App\Models\BookingAudit;
use App\Models\BookingInventoryAudit;
use App\Models\BookingRecurring;
use App\Models\EmailHistory;
use App\Models\InventoryItem;
use App\Models\Income;
use App\Models\Service;
use App\Models\ServiceInventoryUsage;
use App\Models\SmsHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDF;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['customer', 'details.item', 'details.service']);

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

        if ($request->has('dateFrom')) {
            $query->where('start_date', '>=', $request->dateFrom);
        }

        if ($request->has('dateTo')) {
            $query->where('start_date', '<=', $request->dateTo);
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

                $q->orWhereHas('details.item', function ($itemQuery) use ($term) {
                    $itemQuery->where('name', 'like', $term);
                });
            });
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'required|in:active,cancelled,completed,archived',
            'total' => 'required|numeric',
            'duration' => 'required|integer',
            'notes' => 'nullable|string',
            'services' => 'required|array',
            'services.*.item_id' => 'required|exists:customer_items,id',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.service_price' => 'required|numeric',
        ]);

        // Set company_id from authenticated user
        $validated['company_id'] = auth()->user()->company_id;

        $booking = Booking::create($validated);

        // Get unique service IDs to fetch duration
        $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
        $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        // Create booking details from services array
        foreach ($validated['services'] as $serviceItem) {
            $serviceId = $serviceItem['service_id'];
            $booking->details()->create([
                'company_id' => auth()->user()->company_id,
                'item_id' => $serviceItem['item_id'],
                'service_id' => $serviceId,
                'price' => $serviceItem['service_price'],
                'duration' => $services->has($serviceId) ? $services[$serviceId]->duration : 0,
            ]);
        }

        $freshBooking = $booking->fresh(['customer', 'details.item', 'details.service']);

        $this->recordAudit($freshBooking, 'created');
        $this->recordInventoryAudit($freshBooking, 'booking_created');
        if ($freshBooking->status === 'completed') {
            $this->syncInventoryForStatusChange($freshBooking, 'active', 'completed');
        }

        return response()->json($freshBooking, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return response()->json($booking->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Rebook an existing booking into a new active booking on a different date/time.
     */
    public function rebook(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
        ]);

        $booking->load(['customer', 'details.item', 'details.service']);

        if ($booking->details->isEmpty()) {
            return response()->json([
                'message' => 'Unable to rebook this booking because it has no service details.',
            ], 422);
        }

        $details = $booking->details->map(function ($detail) {
            return [
                'item_id' => $detail->item_id,
                'service_id' => $detail->service_id,
                'service_price' => $detail->price,
            ];
        })->values()->all();

        $serviceIds = collect($details)->pluck('service_id')->unique()->toArray();
        $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

        return DB::transaction(function () use ($booking, $validated, $details, $services) {
            $rebooked = Booking::create([
                'company_id' => auth()->user()->company_id,
                'customer_id' => $booking->customer_id,
                'start_date' => $validated['start_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'] ?? $booking->end_time,
                'calendar_color' => $booking->calendar_color,
                'send_sms' => $booking->send_sms,
                'send_email' => $booking->send_email,
                'status' => 'active',
                'total' => $booking->total,
                'duration' => $booking->duration,
                'notes' => $booking->notes,
            ]);

            foreach ($details as $serviceItem) {
                $serviceId = $serviceItem['service_id'];

                $rebooked->details()->create([
                    'company_id' => auth()->user()->company_id,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $serviceId,
                    'price' => $serviceItem['service_price'],
                    'duration' => $services->has($serviceId) ? $services[$serviceId]->duration : 0,
                ]);
            }

            $freshRebooked = $rebooked->fresh(['customer', 'details.item', 'details.service']);

            $this->recordAudit($freshRebooked, 'created_from_rebook', null, [
                'source_booking_id' => $booking->id,
                'source_status' => $booking->status,
            ]);
            $this->recordInventoryAudit($freshRebooked, 'booking_created_from_rebook', [
                'source_booking_id' => $booking->id,
                'source_status' => $booking->status,
            ]);

            $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'rebooked', $booking->status, [
                'rebooked_booking_id' => $freshRebooked->id,
                'rebooked_start_date' => $freshRebooked->start_date,
                'rebooked_start_time' => $freshRebooked->start_time,
            ]);
            $this->recordInventoryAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'booking_rebooked', [
                'rebooked_booking_id' => $freshRebooked->id,
                'rebooked_start_date' => $freshRebooked->start_date,
                'rebooked_start_time' => $freshRebooked->start_time,
            ]);

            return response()->json($freshRebooked, 201);
        });
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $previousStatus = $booking->status;

        $validated = $request->validate([
            'customer_id' => 'sometimes|exists:customers,id',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes',
            'end_time' => 'nullable',
            'calendar_color' => 'nullable|string',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
            'status' => 'sometimes|in:active,cancelled,completed,archived',
            'total' => 'sometimes|numeric',
            'duration' => 'sometimes|integer',
            'notes' => 'nullable|string',
            'services' => 'sometimes|array',
            'services.*.item_id' => 'required_with:services|exists:customer_items,id',
            'services.*.service_id' => 'required_with:services|exists:services,id',
            'services.*.service_price' => 'required_with:services|numeric',
        ]);

        $booking->update($validated);

        if (isset($validated['services'])) {
            $serviceIds = collect($validated['services'])->pluck('service_id')->unique()->toArray();
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

            $booking->details()->delete();
            foreach ($validated['services'] as $serviceItem) {
                $serviceId = $serviceItem['service_id'];
                $booking->details()->create([
                    'company_id' => $booking->company_id,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $serviceId,
                    'price' => $serviceItem['service_price'],
                    'duration' => $services->has($serviceId) ? $services[$serviceId]->duration : 0,
                ]);
            }
        }

        $freshBooking = $booking->fresh(['customer', 'details.item', 'details.service']);

        $this->recordAudit($freshBooking, 'updated', $previousStatus);
        $this->recordInventoryAudit($freshBooking, 'booking_updated', [
            'previous_status' => $previousStatus,
        ]);

        return response()->json($freshBooking);
    }

    /**
     * Update status only.
     * When marking as completed, automatically generate an Income record if one does not already exist.
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,cancelled,completed,archived',
        ]);

        $previousStatus = $booking->status;

        $booking->update(['status' => $validated['status']]);

        $this->syncInventoryForStatusChange($booking, $previousStatus, $validated['status']);

        // Auto-generate income when booking is marked completed for the first time
        if ($validated['status'] === 'completed' && $previousStatus !== 'completed') {
            $alreadyExists = Income::where('booking_id', $booking->id)->exists();

            if (!$alreadyExists) {
                $booking->load('customer');
                $customerName = $booking->customer
                    ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
                    : 'Unknown Customer';

                Income::create([
                    'company_id'         => $booking->company_id,
                    'booking_id'         => $booking->id,
                    'title'              => 'Booking – ' . $customerName,
                    'description'        => 'Auto-generated from completed booking #' . $booking->id,
                    'amount'             => $booking->total ?? 0,
                    'income_date'        => now()->toDateString(),
                    'is_active'          => true,
                ]);
            }
        }

        $booking->load(['customer', 'details.item', 'details.service']);

        $this->recordAudit(
            $booking,
            $this->resolveStatusActionType($previousStatus, $validated['status']),
            $previousStatus
        );
        $this->recordInventoryAudit($booking, 'booking_status_changed', [
            'previous_status' => $previousStatus,
            'new_status' => $validated['status'],
        ]);

        return response()->json($booking);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $this->syncInventoryForStatusChange($booking, $booking->status, 'deleted');
        $this->recordAudit($booking, 'deleted', $booking->status);
        $this->recordInventoryAudit($booking, 'booking_deleted', [
            'status_at_delete' => $booking->status,
        ]);
        $booking->delete();
        return response()->json(null, 204);
    }

    public function getHistory(Booking $booking)
    {
        $history = BookingAudit::where('booking_id', $booking->id)
            ->latest()
            ->paginate(10);

        return response()->json($history);
    }

    public function getInventoryHistory(Booking $booking)
    {
        $history = BookingInventoryAudit::where('booking_id', $booking->id)
            ->latest()
            ->paginate(10);

        return response()->json($history);
    }

    /**
     * Generate invoice PDF for the booking.
     */
    public function generateInvoice(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        
        $pdf = PDF::loadView('bookings.invoice', compact('booking'));
        
        return $pdf->download("invoice-{$booking->id}.pdf");
    }

    /**
     * Generate receipt PDF for the booking.
     */
    public function generateReceipt(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        
        $pdf = PDF::loadView('bookings.receipt', compact('booking'));
        
        return $pdf->download("receipt-{$booking->id}.pdf");
    }

    public function sendInvoice(Booking $booking, Request $request)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (! $customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send an invoice.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $request->user()?->id,
            'from_email' => $request->user()?->email ?? 'no-reply@example.com',
            'to_email' => $customer->email,
            'subject' => 'Invoice for Booking #' . $booking->id,
            'body' => EmailTemplateHelper::generateInvoice($booking),
            'status' => 'sent',
            'mailer_response' => 'Sent from booking detail modal',
            'sent_at' => now(),
        ]);

        $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'send_invoice', $booking->status, [
            'email_history_id' => $record->id,
            'to_email' => $customer->email,
        ]);

        return response()->json([
            'message' => 'Invoice sent successfully.',
            'data' => $record,
        ], 201);
    }

    public function sendReceipt(Booking $booking, Request $request)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (! $customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send a receipt.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $request->user()?->id,
            'from_email' => $request->user()?->email ?? 'no-reply@example.com',
            'to_email' => $customer->email,
            'subject' => 'Receipt for Booking #' . $booking->id,
            'body' => EmailTemplateHelper::generateReceipt($booking),
            'status' => 'sent',
            'mailer_response' => 'Sent from booking detail modal',
            'sent_at' => now(),
        ]);

        $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'send_receipt', $booking->status, [
            'email_history_id' => $record->id,
            'to_email' => $customer->email,
        ]);

        return response()->json([
            'message' => 'Receipt sent successfully.',
            'data' => $record,
        ], 201);
    }

    public function sendSmsConfirmation(Booking $booking, Request $request)
    {
        $booking->load(['customer', 'details.service']);
        $customer = $booking->customer;

        if (! $customer?->phone) {
            throw ValidationException::withMessages([
                'phone' => 'Customer phone is required to send an SMS confirmation.',
            ]);
        }

        $record = SmsHistory::create([
            'user_id' => $request->user()?->id,
            'to_number' => $customer->phone,
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'message' => EmailTemplateHelper::generateSmsConfirmation($booking),
            'status' => 'sent',
            'gateway_response' => 'Sent from booking detail modal',
            'sent_at' => now(),
        ]);

        $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'send_sms_confirmation', $booking->status, [
            'sms_history_id' => $record->id,
            'to_number' => $customer->phone,
        ]);

        return response()->json([
            'message' => 'SMS confirmation sent successfully.',
            'data' => $record,
        ], 201);
    }

    public function sendEmailConfirmation(Booking $booking, Request $request)
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (! $customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send an email confirmation.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $request->user()?->id,
            'from_email' => $request->user()?->email ?? 'no-reply@example.com',
            'to_email' => $customer->email,
            'subject' => 'Booking Confirmation #' . $booking->id,
            'body' => EmailTemplateHelper::generateBookingConfirmation($booking),
            'status' => 'sent',
            'mailer_response' => 'Sent from booking detail modal',
            'sent_at' => now(),
        ]);

        $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'send_email_confirmation', $booking->status, [
            'email_history_id' => $record->id,
            'to_email' => $customer->email,
        ]);

        return response()->json([
            'message' => 'Email confirmation sent successfully.',
            'data' => $record,
        ], 201);
    }

    private function resolveStatusActionType(string $previousStatus, string $newStatus): string
    {
        if ($newStatus === 'completed') {
            return 'completed';
        }

        if ($newStatus === 'cancelled') {
            return 'cancelled';
        }

        if ($newStatus === 'archived') {
            return 'archived';
        }

        if ($newStatus === 'active' && $previousStatus !== 'active') {
            return 'restored';
        }

        return 'status_updated';
    }

    private function recordAudit(Booking $booking, string $actionType, ?string $previousStatus = null, array $meta = []): void
    {
        $booking->loadMissing(['details.item', 'details.service']);

        BookingAudit::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'company_id' => $booking->company_id,
            'action_type' => $actionType,
            'previous_status' => $previousStatus,
            'status' => $booking->status,
            'start_date' => $booking->start_date,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'total' => $booking->total,
            'duration' => $booking->duration,
            'calendar_color' => $booking->calendar_color,
            'send_sms' => (bool) $booking->send_sms,
            'send_email' => (bool) $booking->send_email,
            'notes' => $booking->notes,
            'details_summary' => $booking->details->map(function ($detail) {
                return [
                    'pet_id' => $detail->item_id,
                    'pet_name' => $detail->item->name ?? null,
                    'service_id' => $detail->service_id,
                    'service_name' => $detail->service->name ?? null,
                    'price' => $detail->price,
                    'duration' => $detail->duration,
                ];
            })->values()->all(),
            'meta' => $meta,
        ]);
    }

    private function recordInventoryAudit(Booking $booking, string $changeType, array $meta = []): void
    {
        $booking->loadMissing(['details.item', 'details.service']);

        $customerName = trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''));
        $usageRules = ServiceInventoryUsage::where('company_id', $booking->company_id)
            ->where('is_active', true)
            ->whereIn('service_id', $booking->details->pluck('service_id')->filter()->unique()->values()->all())
            ->get()
            ->groupBy('service_id');

        if ($usageRules->isEmpty()) {
            BookingInventoryAudit::create([
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'inventory_id' => null,
                'inventory_item_name' => null,
                'change_type' => $changeType,
                'quantity_before' => null,
                'quantity_after' => null,
                'quantity_change' => null,
                'notes' => 'Booking-linked inventory audit entry recorded for ' . ($customerName !== '' ? $customerName : ('booking #' . $booking->id)) . '. Add service inventory usage rules to calculate exact deductions.',
                'meta' => array_merge([
                    'status' => $booking->status,
                    'start_date' => $booking->start_date,
                    'start_time' => $booking->start_time,
                    'service_count' => $booking->details->count(),
                    'pet_names' => $booking->details->pluck('item.name')->filter()->values()->all(),
                    'service_names' => $booking->details->pluck('service.name')->filter()->values()->all(),
                ], $meta),
            ]);

            return;
        }

        foreach ($booking->details as $detail) {
            $rules = $usageRules->get($detail->service_id, collect());

            foreach ($rules as $rule) {
                $usageQuantity = (float) $rule->quantity_per_booking;

                BookingInventoryAudit::create([
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'inventory_id' => null,
                    'inventory_item_name' => $rule->inventory_name,
                    'change_type' => $changeType,
                    'quantity_before' => null,
                    'quantity_after' => null,
                    'quantity_change' => -1 * $usageQuantity,
                    'notes' => sprintf(
                        '%s used %s %s for service %s on booking #%s.',
                        $rule->inventory_name,
                        rtrim(rtrim(number_format($usageQuantity, 2, '.', ''), '0'), '.'),
                        $rule->unit,
                        $detail->service->name ?? 'service',
                        $booking->id
                    ),
                    'meta' => array_merge([
                        'service_id' => $detail->service_id,
                        'service_name' => $detail->service->name ?? null,
                        'pet_name' => $detail->item->name ?? null,
                        'usage_rule_id' => $rule->id,
                        'usage_unit' => $rule->unit,
                        'booking_detail_id' => $detail->id,
                        'status' => $booking->status,
                    ], $meta),
                ]);
            }
        }
    }

    private function syncInventoryForStatusChange(Booking $booking, string $previousStatus, string $newStatus): void
    {
        $shouldDeduct = $newStatus === 'completed' && $previousStatus !== 'completed';
        $shouldRestore = $previousStatus === 'completed' && $newStatus !== 'completed';

        if (! $shouldDeduct && ! $shouldRestore) {
            return;
        }

        $booking->loadMissing(['details.service', 'details.item']);

        $usageRules = ServiceInventoryUsage::where('company_id', $booking->company_id)
            ->where('is_active', true)
            ->whereIn('service_id', $booking->details->pluck('service_id')->filter()->unique()->values()->all())
            ->get()
            ->groupBy('service_id');

        foreach ($booking->details as $detail) {
            $rules = $usageRules->get($detail->service_id, collect());

            foreach ($rules as $rule) {
                $inventoryItem = InventoryItem::where('company_id', $booking->company_id)
                    ->where('name', $rule->inventory_name)
                    ->first();

                if (! $inventoryItem) {
                    continue;
                }

                $changeAmount = (float) $rule->quantity_per_booking;
                $signedChange = $shouldDeduct ? -1 * $changeAmount : $changeAmount;
                $before = (float) $inventoryItem->quantity;
                $after = max(0, $before + $signedChange);

                $inventoryItem->update(['quantity' => $after]);

                BookingInventoryAudit::create([
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'inventory_id' => $inventoryItem->id,
                    'inventory_item_name' => $inventoryItem->name,
                    'change_type' => $shouldDeduct ? 'inventory_deducted' : 'inventory_restored',
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'quantity_change' => $signedChange,
                    'notes' => sprintf(
                        '%s %s %s for service %s on booking #%s.',
                        $shouldDeduct ? 'Deducted' : 'Restored',
                        rtrim(rtrim(number_format($changeAmount, 2, '.', ''), '0'), '.'),
                        $rule->unit,
                        $detail->service->name ?? 'service',
                        $booking->id
                    ),
                    'meta' => [
                        'service_id' => $detail->service_id,
                        'service_name' => $detail->service->name ?? null,
                        'booking_detail_id' => $detail->id,
                        'usage_rule_id' => $rule->id,
                        'usage_unit' => $rule->unit,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                    ],
                ]);
            }
        }
    }
}
