<?php

namespace App\Services;

use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Services\BookingServiceInterface;
use App\Helpers\EmailTemplateHelper;
use App\Models\Booking;
use App\Models\BookingAudit;
use App\Models\BookingDetailAudit;
use App\Models\BookingInventoryAudit;
use App\Models\CurrentSoh;
use App\Models\EmailHistory;
use App\Models\Income;
use App\Models\InventoryItem;
use App\Models\ServiceInventoryUsage;
use App\Models\SmsHistory;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PDF;

/**
 * Single Responsibility Principle (SRP):
 * This service handles ONLY business logic for Booking operations.
 * Data access is delegated to the Repository.
 * HTTP concerns are handled by the Controller.
 * 
 * Dependency Inversion Principle (DIP):
 * This service depends on abstractions (interfaces) not concretions.
 * The BookingRepositoryInterface and MessageMediaService are injected.
 */
class BookingService implements BookingServiceInterface
{
    public function __construct(
        protected BookingRepositoryInterface $bookingRepository,
        protected MessageMediaService $smsService
    ) {}

    /**
     * List bookings with filters.
     */
    public function listBookings(array $filters, bool $paginate = false, int $perPage = 25, int $page = 1): Collection|LengthAwarePaginator
    {
        if ($paginate) {
            return $this->bookingRepository->getPaginated($filters, $perPage, $page);
        }

        return $this->bookingRepository->getAll($filters);
    }

    /**
     * Get a single booking by ID.
     */
    public function getBooking(int $id): Booking
    {
        return $this->bookingRepository->findByIdOrFail($id);
    }

    /**
     * Create a new booking with services.
     */
    public function createBooking(array $data, array $services, int $companyId): Booking
    {
        $data['company_id'] = $companyId;
        $booking = $this->bookingRepository->create($data);
        $this->bookingRepository->createBookingDetails($booking, $services, $companyId);

        $freshBooking = $this->bookingRepository->loadFullRelations($booking);

        $this->recordAudit($freshBooking, 'created');
        $this->recordInventoryAudit($freshBooking, 'booking_created');

        if ($freshBooking->status === 'completed') {
            $this->syncInventoryForStatusChange($freshBooking, 'active', 'completed');
        }

        return $freshBooking;
    }

    /**
     * Update an existing booking.
     */
    public function updateBooking(Booking $booking, array $data, ?array $services = null): Booking
    {
        $previousStatus = $booking->status;

        $this->bookingRepository->update($booking, $data);

        if ($services !== null) {
            $this->bookingRepository->updateBookingDetails($booking, $services);
        }

        $freshBooking = $this->bookingRepository->loadFullRelations($booking);

        $this->recordAudit($freshBooking, 'updated', $previousStatus);
        $this->recordInventoryAudit($freshBooking, 'booking_updated', [
            'previous_status' => $previousStatus,
        ]);

        return $freshBooking;
    }

    /**
     * Update booking status with business logic.
     */
    public function updateBookingStatus(Booking $booking, string $newStatus): Booking
    {
        $previousStatus = $booking->status;

        $this->bookingRepository->update($booking, ['status' => $newStatus]);

        $this->syncInventoryForStatusChange($booking, $previousStatus, $newStatus);

        // Auto-generate income when booking is marked completed for the first time
        if ($newStatus === 'completed' && $previousStatus !== 'completed') {
            $this->generateIncomeForCompletedBooking($booking);
        }

        $booking->load(['customer', 'details.item', 'details.service']);

        $this->recordAudit(
            $booking,
            $this->resolveStatusActionType($previousStatus, $newStatus),
            $previousStatus
        );
        $this->recordInventoryAudit($booking, 'booking_status_changed', [
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);

        return $booking;
    }

    /**
     * Delete a booking.
     */
    public function deleteBooking(Booking $booking): void
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $this->syncInventoryForStatusChange($booking, $booking->status, 'deleted');
        $this->recordAudit($booking, 'deleted', $booking->status);
        $this->recordInventoryAudit($booking, 'booking_deleted', [
            'status_at_delete' => $booking->status,
        ]);
        $this->bookingRepository->delete($booking);
    }

    /**
     * Rebook an existing booking to a new date/time.
     */
    public function rebookBooking(Booking $booking, array $newDateTime, int $companyId): Booking
    {
        $booking->load(['customer', 'details.item', 'details.service']);

        if ($booking->details->isEmpty()) {
            throw ValidationException::withMessages([
                'booking' => 'Unable to rebook this booking because it has no service details.',
            ]);
        }

        return DB::transaction(function () use ($booking, $newDateTime, $companyId) {
            $rebookedData = [
                'company_id' => $companyId,
                'customer_id' => $booking->customer_id,
                'start_date' => $newDateTime['start_date'],
                'start_time' => $newDateTime['start_time'],
                'end_time' => $newDateTime['end_time'] ?? $booking->end_time,
                'calendar_color' => $booking->calendar_color,
                'send_sms' => $booking->send_sms,
                'send_email' => $booking->send_email,
                'status' => 'active',
                'total' => $booking->total,
                'duration' => $booking->duration,
                'notes' => $booking->notes,
            ];

            $services = $booking->details->map(fn($detail) => [
                'item_id' => $detail->item_id,
                'service_id' => $detail->service_id,
                'service_price' => $detail->price,
            ])->values()->all();

            $rebooked = $this->bookingRepository->create($rebookedData);
            $this->bookingRepository->createBookingDetails($rebooked, $services, $companyId);

            $freshRebooked = $this->bookingRepository->loadFullRelations($rebooked);

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

            return $freshRebooked;
        });
    }

    /**
     * Send SMS confirmation for a booking.
     */
    public function sendSmsConfirmation(Booking $booking, ?int $userId, ?int $companyId): array
    {
        $booking->load(['customer', 'details.service']);
        $customer = $booking->customer;

        if (!$customer?->phone) {
            throw ValidationException::withMessages([
                'phone' => 'Customer phone is required to send an SMS confirmation.',
            ]);
        }

        if (!$this->smsService->isValidPhoneNumber($customer->phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Invalid phone number format.',
            ]);
        }

        $message = EmailTemplateHelper::generateSmsConfirmation($booking);
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));

        $result = $this->smsService->sendSms($customer->phone, $message, [
            'source_name' => config('services.messagemedia.source_name'),
            'metadata' => [
                'booking_id' => $booking->id,
                'customer_id' => $customer->id,
            ],
        ]);

        $record = SmsHistory::create([
            'company_id' => $companyId,
            'to_number' => $this->smsService->formatPhoneNumber($customer->phone),
            'customer_name' => $customerName,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'gateway_response' => json_encode([
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                'parts' => $result['parts'] ?? 1,
            ]),
            'sent_at' => $result['success'] ? now() : null,
        ]);

        $this->recordAudit($booking->fresh(['customer', 'details.item', 'details.service']), 'send_sms_confirmation', $booking->status, [
            'sms_history_id' => $record->id,
            'to_number' => $customer->phone,
            'gateway_success' => $result['success'],
        ]);

        return [
            'success' => $result['success'],
            'record' => $record,
            'error' => $result['error'] ?? null,
        ];
    }

    /**
     * Send email confirmation for a booking.
     */
    public function sendEmailConfirmation(Booking $booking, ?int $userId, ?string $userEmail): array
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (!$customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send an email confirmation.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $userId,
            'from_email' => $userEmail ?? 'no-reply@example.com',
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

        return ['success' => true, 'record' => $record];
    }

    /**
     * Send invoice for a booking.
     */
    public function sendInvoice(Booking $booking, ?int $userId, ?string $userEmail): array
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (!$customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send an invoice.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $userId,
            'from_email' => $userEmail ?? 'no-reply@example.com',
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

        return ['success' => true, 'record' => $record];
    }

    /**
     * Send receipt for a booking.
     */
    public function sendReceipt(Booking $booking, ?int $userId, ?string $userEmail): array
    {
        $booking->load(['customer', 'details.item', 'details.service']);
        $customer = $booking->customer;

        if (!$customer?->email) {
            throw ValidationException::withMessages([
                'email' => 'Customer email is required to send a receipt.',
            ]);
        }

        $record = EmailHistory::create([
            'user_id' => $userId,
            'from_email' => $userEmail ?? 'no-reply@example.com',
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

        return ['success' => true, 'record' => $record];
    }

    /**
     * Get booking audit history.
     */
    public function getAuditHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator
    {
        return BookingAudit::where('booking_id', $booking->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get booking inventory audit history.
     */
    public function getInventoryHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator
    {
        return BookingInventoryAudit::where('booking_id', $booking->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get booking detail audit history.
     */
    public function getDetailHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator
    {
        return BookingDetailAudit::where('booking_id', $booking->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Get stock usages for a booking.
     */
    public function getStockUsages(Booking $booking, int $perPage = 10): LengthAwarePaginator
    {
        return StockMovement::with(['inventory:id,name,unit_id', 'inventory.unit:id,name'])
            ->where('reference_type', 'booking')
            ->where('reference_id', $booking->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Generate invoice PDF for a booking.
     */
    public function generateInvoicePdf(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service', 'company']);
        $company = $booking->company;
        $invoiceNumber = 4060000 + $booking->id;

        return PDF::loadView('bookings.invoice', compact('booking', 'company', 'invoiceNumber'));
    }

    /**
     * Generate receipt PDF for a booking.
     */
    public function generateReceiptPdf(Booking $booking)
    {
        $booking->load(['customer', 'details.item', 'details.service', 'company']);
        $company = $booking->company;
        $invoiceNumber = 4060000 + $booking->id;

        return PDF::loadView('bookings.receipt', compact('booking', 'company', 'invoiceNumber'));
    }

    // ─── Private Helper Methods ─────────────────────────────────────────────────

    /**
     * Generate income record for completed booking.
     */
    protected function generateIncomeForCompletedBooking(Booking $booking): void
    {
        $alreadyExists = Income::where('booking_id', $booking->id)->exists();

        if ($alreadyExists) {
            return;
        }

        $booking->load('customer');
        $customerName = $booking->customer
            ? trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''))
            : 'Unknown Customer';

        Income::create([
            'company_id' => $booking->company_id,
            'booking_id' => $booking->id,
            'title' => 'Booking – ' . $customerName,
            'description' => 'Auto-generated from completed booking #' . $booking->id,
            'amount' => $booking->total ?? 0,
            'income_date' => now()->toDateString(),
            'is_active' => true,
        ]);
    }

    /**
     * Resolve the action type based on status change.
     */
    protected function resolveStatusActionType(string $previousStatus, string $newStatus): string
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

    /**
     * Record audit entry for booking.
     */
    protected function recordAudit(Booking $booking, string $actionType, ?string $previousStatus = null, array $meta = []): void
    {
        $booking->loadMissing(['details.item', 'details.service']);

        BookingAudit::create([
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'company_id' => $booking->company_id,
            'action_type' => $actionType,
            'action_at' => now(),
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

    /**
     * Record inventory audit entry for booking.
     */
    protected function recordInventoryAudit(Booking $booking, string $changeType, array $meta = []): void
    {
        $booking->loadMissing(['details.item', 'details.service']);

        $customerName = trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''));
        $usageRules = ServiceInventoryUsage::where('is_active', true)
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
                'action_at' => now(),
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
                    'action_at' => now(),
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

    /**
     * Sync inventory for status change.
     */
    protected function syncInventoryForStatusChange(Booking $booking, string $previousStatus, string $newStatus): void
    {
        $shouldDeduct = $newStatus === 'completed' && $previousStatus !== 'completed';
        $shouldRestore = $previousStatus === 'completed' && $newStatus !== 'completed';

        if (!$shouldDeduct && !$shouldRestore) {
            return;
        }

        $booking->loadMissing(['details.service', 'details.item']);

        $serviceIds = $booking->details->pluck('service_id')->filter()->unique()->values()->all();

        if (empty($serviceIds)) {
            return;
        }

        $usageRules = ServiceInventoryUsage::with('unit')
            ->where('company_id', $booking->company_id)
            ->where('is_active', true)
            ->whereIn('service_id', $serviceIds)
            ->get()
            ->groupBy('service_id');

        if ($usageRules->isEmpty()) {
            return;
        }

        foreach ($booking->details as $detail) {
            $rules = $usageRules->get($detail->service_id, collect());

            foreach ($rules as $rule) {
                $inventoryItem = InventoryItem::where('company_id', $booking->company_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($rule->inventory_name)])
                    ->first();

                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::create([
                        'company_id' => $booking->company_id,
                        'name' => $rule->inventory_name,
                        'category' => 'General',
                        'sku' => null,
                        'quantity' => 0,
                        'min_stock' => 0,
                        'unit_price' => 0,
                        'unit_id' => $rule->unit_id,
                        'notes' => 'Auto-created from service inventory usage rule',
                        'is_active' => true,
                    ]);
                }

                $changeAmount = (float) $rule->quantity_per_booking;
                $signedChange = $shouldDeduct ? -1 * $changeAmount : $changeAmount;
                $before = (float) $inventoryItem->quantity;
                $after = max(0, $before + $signedChange);

                $inventoryItem->update(['quantity' => $after]);

                $currentSoh = CurrentSoh::where('company_id', $booking->company_id)
                    ->where('inventory_id', $inventoryItem->id)
                    ->first();

                if ($currentSoh) {
                    $sohBefore = (int) $currentSoh->current_quantity;
                    $sohAfter = max(0, $sohBefore + (int) $signedChange);
                    $currentSoh->update(['current_quantity' => $sohAfter]);
                } else {
                    $sohBefore = 0;
                    $sohAfter = max(0, $sohBefore + (int) $signedChange);
                    $currentSoh = CurrentSoh::create([
                        'company_id' => $booking->company_id,
                        'category_id' => null,
                        'inventory_id' => $inventoryItem->id,
                        'current_quantity' => $sohAfter,
                        'current_percentage' => 0,
                    ]);
                }

                StockMovement::create([
                    'company_id' => $booking->company_id,
                    'category_id' => $currentSoh->category_id ?? null,
                    'inventory_id' => $inventoryItem->id,
                    'batch_id' => null,
                    'movement_type' => 'booking_usage',
                    'quantity_change' => (int) $signedChange,
                    'percentage_change' => 0,
                    'quantity_before' => (int) $before,
                    'quantity_after' => (int) $after,
                    'percentage_before' => 0,
                    'percentage_after' => 0,
                    'reference_type' => 'booking',
                    'reference_id' => $booking->id,
                    'notes' => sprintf(
                        '%s %s %s for service "%s" (booking #%s)',
                        $shouldDeduct ? 'Deducted' : 'Restored',
                        rtrim(rtrim(number_format($changeAmount, 2, '.', ''), '0'), '.'),
                        $rule->unit?->name ?? 'units',
                        $detail->service->name ?? 'service',
                        $booking->id
                    ),
                    'performed_by' => auth()->id(),
                ]);

                BookingInventoryAudit::create([
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'inventory_id' => $inventoryItem->id,
                    'inventory_item_name' => $inventoryItem->name,
                    'change_type' => $shouldDeduct ? 'inventory_deducted' : 'inventory_restored',
                    'action_at' => now(),
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'quantity_change' => $signedChange,
                    'notes' => sprintf(
                        '%s %s %s for service %s on booking #%s.',
                        $shouldDeduct ? 'Deducted' : 'Restored',
                        rtrim(rtrim(number_format($changeAmount, 2, '.', ''), '0'), '.'),
                        $rule->unit?->name ?? 'units',
                        $detail->service->name ?? 'service',
                        $booking->id
                    ),
                    'meta' => [
                        'service_id' => $detail->service_id,
                        'service_name' => $detail->service->name ?? null,
                        'pet_name' => $detail->item->name ?? null,
                        'usage_rule_id' => $rule->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                    ],
                ]);
            }
        }
    }
}
