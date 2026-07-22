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

        $scheduleChanged = array_intersect(
            array_keys($data),
            ['start_date', 'frequency', 'repeat_day', 'repeat_time', 'repeat_until']
        ) !== [];

        $bookingRecurring = $this->bookingRecurringRepository->update($bookingRecurring, $data);

        if ($detailsData !== null) {
            // Delete existing details and recreate
            $this->bookingRecurringRepository->deleteDetails($bookingRecurring);
            foreach ($detailsData as $detail) {
                $this->bookingRecurringRepository->createDetail($bookingRecurring, $detail);
            }
        }

        if ($scheduleChanged || $detailsData !== null) {
            $today = now()->format('Y-m-d');
            $bookingRecurring->bookings()
                ->where('start_date', '>=', $today)
                ->where('status', 'active')
                ->delete();

            $this->generateBookings($bookingRecurring->fresh(['details']));
        }

        return $bookingRecurring->fresh(['customer', 'details.item', 'details.service', 'bookings']);
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

        $repeatUntil = $bookingRecurring->repeat_until
            ? Carbon::parse($bookingRecurring->repeat_until)
            : now()->addMonths(3);
        $frequency = max((int) $bookingRecurring->frequency, 1);

        $currentDate = Carbon::parse($bookingRecurring->start_date);

        // Align to the configured day of week, if set
        if (!empty($bookingRecurring->repeat_day)) {
            $dayMap = [
                'Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3,
                'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6,
            ];
            if (array_key_exists($bookingRecurring->repeat_day, $dayMap)) {
                $daysUntilTarget = ($dayMap[$bookingRecurring->repeat_day] - $currentDate->dayOfWeek + 7) % 7;
                $currentDate->addDays($daysUntilTarget);
            }
        }

        // Get list of already created booking dates
        $existingDates = $bookingRecurring->bookings()
            ->pluck('start_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        while ($currentDate->lte($repeatUntil)) {
            $dateStr = $currentDate->format('Y-m-d');

            if (!in_array($dateStr, $existingDates, true) && $currentDate->gte(now()->startOfDay())) {
                $this->createBookingFromRecurring($bookingRecurring, $currentDate);
            }

            $currentDate = $currentDate->copy()->addWeeks($frequency);
        }
    }

    public function getHistory(BookingRecurring $bookingRecurring): LengthAwarePaginator
    {
        return $bookingRecurring->bookings()
            ->orderByDesc('start_date')
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
            'recurring_id' => $recurring->id,
            'start_date' => $date->format('Y-m-d'),
            'start_time' => $recurring->repeat_time,
            'calendar_color' => $recurring->color,
            'notes' => $recurring->notes,
            'status' => 'active',
            'total' => $recurring->total,
            'duration' => $recurring->duration,
        ]);

        // Copy details to booking
        foreach ($recurring->details as $detail) {
            $booking->details()->create([
                'company_id' => $recurring->company_id,
                'item_id' => $detail->item_id,
                'service_id' => $detail->service_id,
                'price' => $detail->price,
                'duration' => $detail->duration,
            ]);
        }

        return $booking;
    }
}
