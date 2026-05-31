<?php

namespace App\Services;

use App\Contracts\Repositories\WaitlistRepositoryInterface;
use App\Contracts\Services\WaitlistServiceInterface;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Waitlist;
use App\Models\WaitlistAudit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WaitlistService implements WaitlistServiceInterface
{
    public function __construct(
        private WaitlistRepositoryInterface $waitlistRepository
    ) {}

    public function listWaitlists(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        // Add company_id filter from auth
        if (Auth::check() && Auth::user()->company_id) {
            $filters['company_id'] = Auth::user()->company_id;
        }

        return $this->waitlistRepository->getPaginated($filters, $perPage);
    }

    public function getWaitlist(int $id): Waitlist
    {
        return $this->waitlistRepository->findByIdOrFail($id, ['customer', 'details.item', 'details.service']);
    }

    public function createWaitlist(array $data): Waitlist
    {
        $companyId = Auth::user()->company_id;
        $data['company_id'] = $companyId;
        $data['status'] = $data['status'] ?? 'active';

        $waitlist = $this->waitlistRepository->create($data);

        // Create details
        if (!empty($data['services'])) {
            $serviceIds = collect($data['services'])->pluck('service_id')->unique()->toArray();
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

            foreach ($data['services'] as $serviceItem) {
                $sid = $serviceItem['service_id'];
                $waitlist->details()->create([
                    'company_id' => $companyId,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $sid,
                    'price' => $serviceItem['service_price'],
                    'duration' => $services->has($sid) ? $services[$sid]->duration : 0,
                ]);
            }
        }

        // Create audit record
        $this->createAuditRecord($waitlist, 'created');

        return $waitlist->fresh(['customer', 'details.item', 'details.service']);
    }

    public function updateWaitlist(Waitlist $waitlist, array $data): Waitlist
    {
        $previousStatus = $waitlist->status;
        $waitlist = $this->waitlistRepository->update($waitlist, $data);

        if (!empty($data['services'])) {
            $waitlist->details()->delete();

            $companyId = Auth::user()->company_id;
            $serviceIds = collect($data['services'])->pluck('service_id')->unique()->toArray();
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');

            foreach ($data['services'] as $serviceItem) {
                $sid = $serviceItem['service_id'];
                $waitlist->details()->create([
                    'company_id' => $companyId,
                    'item_id' => $serviceItem['item_id'],
                    'service_id' => $sid,
                    'price' => $serviceItem['service_price'],
                    'duration' => $services->has($sid) ? $services[$sid]->duration : 0,
                ]);
            }
        }

        // Create audit record
        $this->createAuditRecord($waitlist, 'updated', $previousStatus);

        return $waitlist->fresh(['customer', 'details.item', 'details.service']);
    }

    public function deleteWaitlist(Waitlist $waitlist): bool
    {
        // Create audit record before deletion
        $this->createAuditRecord($waitlist, 'deleted');

        return $this->waitlistRepository->delete($waitlist);
    }

    public function updateStatus(Waitlist $waitlist, string $status): Waitlist
    {
        $previousStatus = $waitlist->status;
        $waitlist = $this->waitlistRepository->update($waitlist, ['status' => $status]);

        // Create audit record
        $this->createAuditRecord($waitlist, 'status_changed', $previousStatus);

        return $waitlist->fresh(['customer', 'details.item', 'details.service']);
    }

    public function convertToBooking(Waitlist $waitlist): array
    {
        return DB::transaction(function () use ($waitlist) {
            $waitlist->load(['customer', 'details.item', 'details.service']);

            $booking = Booking::create([
                'company_id' => Auth::user()->company_id,
                'customer_id' => $waitlist->customer_id,
                'start_date' => $waitlist->start_date,
                'start_time' => $waitlist->start_time,
                'end_time' => $waitlist->end_time,
                'calendar_color' => $waitlist->calendar_color,
                'send_sms' => $waitlist->send_sms,
                'send_email' => $waitlist->send_email,
                'status' => 'active',
                'total' => $waitlist->total,
                'duration' => $waitlist->duration,
                'notes' => $waitlist->notes,
            ]);

            foreach ($waitlist->details as $detail) {
                $booking->details()->create([
                    'company_id' => Auth::user()->company_id,
                    'item_id' => $detail->item_id,
                    'service_id' => $detail->service_id,
                    'price' => $detail->price,
                    'duration' => $detail->duration,
                ]);
            }

            $previousStatus = $waitlist->status;
            $waitlist->update(['status' => 'completed']);

            // Create audit record
            $this->createAuditRecord($waitlist, 'converted_to_booking', $previousStatus, [
                'booking_id' => $booking->id,
            ]);

            return [
                'booking' => $booking->fresh(['customer', 'details.item', 'details.service']),
                'waitlist' => $waitlist->fresh(),
            ];
        });
    }

    public function sendEmailConfirmation(Waitlist $waitlist): void
    {
        // Create audit record
        $this->createAuditRecord($waitlist, 'email_sent');
    }

    public function getWaitlistHistory(Waitlist $waitlist): LengthAwarePaginator
    {
        return WaitlistAudit::where('waitlist_id', $waitlist->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(10);
    }

    private function createAuditRecord(Waitlist $waitlist, string $actionType, ?string $previousStatus = null, array $meta = []): void
    {
        $waitlist->load(['details.item', 'details.service']);

        $detailsSummary = $waitlist->details->map(function ($detail) {
            return [
                'item_id' => $detail->item_id,
                'item_name' => $detail->item?->name ?? null,
                'service_id' => $detail->service_id,
                'service_name' => $detail->service?->name ?? null,
                'price' => $detail->price,
                'duration' => $detail->duration,
            ];
        })->toArray();

        WaitlistAudit::create([
            'waitlist_id' => $waitlist->id,
            'customer_id' => $waitlist->customer_id,
            'company_id' => $waitlist->company_id,
            'action_type' => $actionType,
            'previous_status' => $previousStatus,
            'status' => $waitlist->status,
            'start_date' => $waitlist->start_date,
            'end_date' => $waitlist->end_date,
            'start_time' => $waitlist->start_time,
            'end_time' => $waitlist->end_time,
            'total' => $waitlist->total,
            'duration' => $waitlist->duration,
            'calendar_color' => $waitlist->calendar_color,
            'send_sms' => $waitlist->send_sms,
            'send_email' => $waitlist->send_email,
            'notes' => $waitlist->notes,
            'details_summary' => $detailsSummary,
            'meta' => !empty($meta) ? $meta : null,
        ]);
    }
}
