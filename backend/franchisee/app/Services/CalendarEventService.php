<?php

namespace App\Services;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Contracts\Services\CalendarEventServiceInterface;
use App\Models\Booking;
use App\Models\Blockout;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarEventService implements CalendarEventServiceInterface
{
    public function __construct(
        protected CalendarEventRepositoryInterface $repository
    ) {}

    public function index(int $companyId, string $startDate, string $endDate, ?string $eventType = null): Collection
    {
        return $this->repository->findByDateRange($companyId, $startDate, $endDate, $eventType);
    }

    public function getByMonth(int $companyId, int $year, int $month): Collection
    {
        return $this->repository->findByMonth($companyId, $year, $month);
    }

    public function create(array $data): CalendarEvent
    {
        if (isset($data['start_time'])) {
            $data['start_time'] = $this->convertTo24Hour($data['start_time']);
        }
        if (isset($data['end_time'])) {
            $data['end_time'] = $this->convertTo24Hour($data['end_time']);
        }

        return $this->repository->create($data);
    }

    public function show(CalendarEvent $event): CalendarEvent
    {
        return $event->load('customer', 'booking', 'blockout');
    }

    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        return $this->repository->update($event, $data);
    }

    public function delete(CalendarEvent $event): void
    {
        $this->repository->delete($event);
    }

    public function syncEvents(int $companyId): array
    {
        // Clear existing events
        $this->repository->deleteByCompanyId($companyId);

        // Sync bookings
        $bookings = Booking::where('company_id', $companyId)->get();
        foreach ($bookings as $booking) {
            $this->repository->create([
                'company_id' => $companyId,
                'event_type' => 'booking',
                'title' => $booking->customer?->name ?? 'Booking',
                'description' => $booking->notes,
                'start_date' => $booking->start_date,
                'start_time' => $this->convertTo24Hour($booking->start_time),
                'end_date' => $booking->start_date,
                'end_time' => $this->convertTo24Hour($booking->end_time),
                'color' => $booking->calendar_color ?? '#3b82f6',
                'customer_id' => $booking->customer_id,
                'booking_id' => $booking->id,
                'is_recurring' => !!$booking->recurring_id,
                'is_active' => $booking->status !== 'cancelled',
            ]);
        }

        // Sync blockouts
        $blockouts = Blockout::where('company_id', $companyId)->get();
        foreach ($blockouts as $blockout) {
            $this->repository->create([
                'company_id' => $companyId,
                'event_type' => 'blockout',
                'title' => $blockout->title,
                'description' => $blockout->notes,
                'start_date' => $blockout->start_date,
                'start_time' => $this->convertTo24Hour($blockout->start_time),
                'end_date' => $blockout->end_date,
                'end_time' => $this->convertTo24Hour($blockout->end_time),
                'location' => $blockout->location,
                'color' => '#9333ea',
                'blockout_id' => $blockout->id,
                'is_recurring' => !!$blockout->recurring_id,
                'is_active' => $blockout->active,
            ]);
        }

        return ['message' => 'Calendar events synced successfully'];
    }

    private function convertTo24Hour(?string $time): string
    {
        if (!$time) return '00:00:00';
        try {
            return Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
        } catch (\Exception $e) {
            return $time;
        }
    }
}
