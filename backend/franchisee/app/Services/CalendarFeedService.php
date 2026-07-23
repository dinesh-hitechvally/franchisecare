<?php

namespace App\Services;

use App\Contracts\Repositories\BlockoutRepositoryInterface;
use App\Contracts\Repositories\BookingRepositoryInterface;
use App\Contracts\Services\CalendarFeedServiceInterface;
use App\Models\Blockout;
use App\Models\Booking;

class CalendarFeedService implements CalendarFeedServiceInterface
{
    private const BLOCKOUT_COLOR = '#9333ea';
    private const VISIBLE_BOOKING_STATUSES = ['active', 'completed'];

    public function __construct(
        private BookingRepositoryInterface $bookingRepository,
        private BlockoutRepositoryInterface $blockoutRepository
    ) {}

    public function getEvents(int $companyId, string $dateFrom, string $dateTo): array
    {
        $bookings = $this->bookingRepository->getAll([
            'company_id' => $companyId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])->filter(fn (Booking $booking) => in_array($booking->status, self::VISIBLE_BOOKING_STATUSES, true));

        $blockouts = $this->blockoutRepository->getAll([
            'company_id' => $companyId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'active' => true,
        ]);

        return $bookings->map(fn (Booking $booking) => $this->mapBooking($booking))
            ->concat($blockouts->map(fn (Blockout $blockout) => $this->mapBlockout($blockout)))
            ->values()
            ->all();
    }

    private function mapBooking(Booking $booking): array
    {
        $petNames = $booking->details
            ->map(fn ($detail) => $detail->item?->name)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $petBreeds = $booking->details
            ->map(fn ($detail) => $detail->item?->breed)
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        $serviceNames = $booking->details
            ->map(fn ($detail) => $detail->service?->name)
            ->filter()
            ->values()
            ->implode(', ');

        $customerName = trim(($booking->customer->first_name ?? '') . ' ' . ($booking->customer->last_name ?? ''));

        return [
            'id' => "booking-{$booking->id}",
            'event_type' => 'booking',
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer_id,
            'recurring_id' => $booking->recurring_id,
            'start_date' => optional($booking->start_date)->format('Y-m-d'),
            'end_date' => null,
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'duration' => $booking->duration,
            'status' => $booking->status,
            'calendar_color' => $booking->calendar_color,
            'total' => $booking->total,
            'notes' => $booking->notes,
            'customer_name' => $customerName !== '' ? $customerName : 'Booking',
            'customer_address' => $booking->customer->street_address ?? $booking->customer->address ?? null,
            'pet_name' => $petNames,
            'pet_breed' => $petBreeds,
            'service_name' => $serviceNames,
        ];
    }

    private function mapBlockout(Blockout $blockout): array
    {
        return [
            'id' => "blockout-{$blockout->id}",
            'event_type' => 'blockout',
            'blockout_id' => $blockout->id,
            'title' => $blockout->title,
            'location' => $blockout->location,
            'start_date' => optional($blockout->start_date)->format('Y-m-d'),
            'end_date' => optional($blockout->end_date)->format('Y-m-d'),
            'start_time' => $blockout->start_time,
            'end_time' => $blockout->end_time,
            'status' => $blockout->active ? 'active' : 'cancelled',
            'calendar_color' => self::BLOCKOUT_COLOR,
            'notes' => $blockout->notes,
        ];
    }
}
