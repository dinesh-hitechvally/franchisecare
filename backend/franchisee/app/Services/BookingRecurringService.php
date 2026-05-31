<?php

namespace App\Services;

use App\Contracts\Repositories\BookingRecurringRepositoryInterface;
use App\Contracts\Services\BookingRecurringServiceInterface;
use App\Models\BookingRecurring;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class BookingRecurringService implements BookingRecurringServiceInterface
{
    public function __construct(
        private BookingRecurringRepositoryInterface $bookingRecurringRepository
    ) {}

    public function listBookingRecurrings(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        if ($perPage !== null) {
            return $this->bookingRecurringRepository->getPaginated($filters, $perPage);
        }

        return $this->bookingRecurringRepository->getAll($filters);
    }

    public function getBookingRecurring(int $id): BookingRecurring
    {
        return $this->bookingRecurringRepository->findByIdOrFail($id, ['customer', 'details.item', 'details.service', 'bookings']);
    }

    public function createBookingRecurring(array $data): BookingRecurring
    {
        $detailsData = $data['details'] ?? [];
        unset($data['details']);

        $bookingRecurring = $this->bookingRecurringRepository->create($data);

        // Create details
        foreach ($detailsData as $detail) {
            $this->bookingRecurringRepository->createDetail($bookingRecurring, $detail);
        }

        // Generate initial bookings
        $this->generateBookings($bookingRecurring->fresh(['details']));

        return $bookingRecurring->load(['customer', 'details.item', 'details.service', 'bookings']);
    }

    public function updateBookingRecurring(BookingRecurring $bookingRecurring, array $data): BookingRecurring
    {
        $detailsData = $data['details'] ?? null;
        unset($data['details']);

        $bookingRecurring = $this->bookingRecurringRepository->update($bookingRecurring, $data);

        if ($detailsData !== null) {
            // Delete existing details and recreate
            $this->bookingRecurringRepository->deleteDetails($bookingRecurring);
            foreach ($detailsData as $detail) {
                $this->bookingRecurringRepository->createDetail($bookingRecurring, $detail);
            }
        }

        return $bookingRecurring->load(['customer', 'details.item', 'details.service', 'bookings']);
    }

    public function updateStatus(BookingRecurring $bookingRecurring, string $status): BookingRecurring
    {
        return $this->bookingRecurringRepository->update($bookingRecurring, ['status' => $status]);
    }

    public function deleteBookingRecurring(BookingRecurring $bookingRecurring): bool
    {
        return $this->bookingRecurringRepository->delete($bookingRecurring);
    }

    public function generateBookings(BookingRecurring $bookingRecurring): void
    {
        if ($bookingRecurring->status !== 'active') {
            return;
        }

        $startDate = Carbon::parse($bookingRecurring->start_date);
        $repeatUntil = $bookingRecurring->repeat_until 
            ? Carbon::parse($bookingRecurring->repeat_until) 
            : now()->addMonths(3);
        $frequency = $bookingRecurring->frequency;
        $interval = $bookingRecurring->interval;

        // Get list of already created booking dates
        $existingDates = $bookingRecurring->bookings()
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($repeatUntil)) {
            $dateStr = $currentDate->format('Y-m-d');

            if (!in_array($dateStr, $existingDates) && $currentDate->gte(now()->startOfDay())) {
                $this->createBookingFromRecurring($bookingRecurring, $currentDate);
            }

            // Move to next occurrence based on frequency
            $currentDate = $this->advanceDate($currentDate, $frequency, $interval);
        }
    }

    public function getHistory(BookingRecurring $bookingRecurring): LengthAwarePaginator
    {
        return $bookingRecurring->bookings()
            ->orderByDesc('date')
            ->paginate(20);
    }

    public function getDetailHistory(BookingRecurring $bookingRecurring): LengthAwarePaginator
    {
        return $bookingRecurring->details()
            ->with(['item', 'service'])
            ->paginate(20);
    }

    private function createBookingFromRecurring(BookingRecurring $recurring, Carbon $date): Booking
    {
        $booking = Booking::create([
            'company_id' => $recurring->company_id,
            'customer_id' => $recurring->customer_id,
            'booking_recurring_id' => $recurring->id,
            'date' => $date->format('Y-m-d'),
            'start_time' => $recurring->start_time,
            'end_time' => $recurring->end_time,
            'notes' => $recurring->notes,
            'status' => 'confirmed',
        ]);

        // Copy details to booking
        foreach ($recurring->details as $detail) {
            $booking->details()->create([
                'item_id' => $detail->item_id,
                'service_id' => $detail->service_id,
                'price' => $detail->price,
            ]);
        }

        return $booking;
    }

    private function advanceDate(Carbon $date, string $frequency, int $interval): Carbon
    {
        return match ($frequency) {
            'daily' => $date->addDays($interval),
            'weekly' => $date->addWeeks($interval),
            'fortnightly' => $date->addWeeks($interval * 2),
            'monthly' => $date->addMonths($interval),
            'yearly' => $date->addYears($interval),
            default => $date->addWeeks($interval),
        };
    }
}
