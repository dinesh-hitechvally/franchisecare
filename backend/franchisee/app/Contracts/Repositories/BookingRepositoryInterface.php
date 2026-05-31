<?php

namespace App\Contracts\Repositories;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface Segregation Principle (ISP):
 * This interface defines only the data access methods needed for Booking operations.
 * It is focused on a single concern - booking data persistence.
 */
interface BookingRepositoryInterface
{
    /**
     * Get all bookings with optional filters.
     *
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Get paginated bookings with optional filters.
     *
     * @param array $filters
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getPaginated(array $filters = [], int $perPage = 25, int $page = 1): LengthAwarePaginator;

    /**
     * Find a booking by ID.
     *
     * @param int $id
     * @param array $relations
     * @return Booking|null
     */
    public function findById(int $id, array $relations = []): ?Booking;

    /**
     * Find a booking by ID or fail.
     *
     * @param int $id
     * @param array $relations
     * @return Booking
     */
    public function findByIdOrFail(int $id, array $relations = []): Booking;

    /**
     * Create a new booking.
     *
     * @param array $data
     * @return Booking
     */
    public function create(array $data): Booking;

    /**
     * Update an existing booking.
     *
     * @param Booking $booking
     * @param array $data
     * @return Booking
     */
    public function update(Booking $booking, array $data): Booking;

    /**
     * Delete a booking.
     *
     * @param Booking $booking
     * @return bool
     */
    public function delete(Booking $booking): bool;

    /**
     * Create booking details for a booking.
     *
     * @param Booking $booking
     * @param array $services
     * @param int $companyId
     * @return void
     */
    public function createBookingDetails(Booking $booking, array $services, int $companyId): void;

    /**
     * Update booking details (delete and recreate).
     *
     * @param Booking $booking
     * @param array $services
     * @return void
     */
    public function updateBookingDetails(Booking $booking, array $services): void;

    /**
     * Get booking with all relations loaded.
     *
     * @param Booking $booking
     * @return Booking
     */
    public function loadFullRelations(Booking $booking): Booking;
}
