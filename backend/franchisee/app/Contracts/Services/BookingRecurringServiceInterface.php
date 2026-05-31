<?php

namespace App\Contracts\Services;

use App\Models\BookingRecurring;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookingRecurringServiceInterface
{
    public function listBookingRecurrings(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator;

    public function getBookingRecurring(int $id): BookingRecurring;

    public function createBookingRecurring(array $data): BookingRecurring;

    public function updateBookingRecurring(BookingRecurring $bookingRecurring, array $data): BookingRecurring;

    public function updateStatus(BookingRecurring $bookingRecurring, string $status): BookingRecurring;

    public function deleteBookingRecurring(BookingRecurring $bookingRecurring): bool;

    public function generateBookings(BookingRecurring $bookingRecurring): void;

    public function getHistory(BookingRecurring $bookingRecurring): LengthAwarePaginator;

    public function getDetailHistory(BookingRecurring $bookingRecurring): LengthAwarePaginator;
}
