<?php

namespace App\Contracts\Services;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface Segregation Principle (ISP):
 * This interface defines the business logic operations for Booking.
 * It focuses on use cases rather than CRUD operations.
 */
interface BookingServiceInterface
{
    /**
     * List bookings with filters.
     *
     * @param array $filters
     * @param bool $paginate
     * @param int $perPage
     * @param int $page
     * @return Collection|LengthAwarePaginator
     */
    public function listBookings(array $filters, bool $paginate = false, int $perPage = 25, int $page = 1): Collection|LengthAwarePaginator;

    /**
     * Get a single booking by ID.
     *
     * @param int $id
     * @return Booking
     */
    public function getBooking(int $id): Booking;

    /**
     * Create a new booking with services.
     *
     * @param array $data
     * @param array $services
     * @param int $companyId
     * @return Booking
     */
    public function createBooking(array $data, array $services, int $companyId): Booking;

    /**
     * Update an existing booking.
     *
     * @param Booking $booking
     * @param array $data
     * @param array|null $services
     * @return Booking
     */
    public function updateBooking(Booking $booking, array $data, ?array $services = null): Booking;

    /**
     * Update booking status with business logic (income generation, inventory sync).
     *
     * @param Booking $booking
     * @param string $newStatus
     * @return Booking
     */
    public function updateBookingStatus(Booking $booking, string $newStatus): Booking;

    /**
     * Delete a booking.
     *
     * @param Booking $booking
     * @return void
     */
    public function deleteBooking(Booking $booking): void;

    /**
     * Rebook an existing booking to a new date/time.
     *
     * @param Booking $booking
     * @param array $newDateTime
     * @param int $companyId
     * @return Booking
     */
    public function rebookBooking(Booking $booking, array $newDateTime, int $companyId): Booking;

    /**
     * Send SMS confirmation for a booking.
     *
     * @param Booking $booking
     * @param int|null $userId
     * @param int|null $companyId
     * @return array ['success' => bool, 'record' => SmsHistory, 'error' => ?string]
     */
    public function sendSmsConfirmation(Booking $booking, ?int $userId, ?int $companyId): array;

    /**
     * Send email confirmation for a booking.
     *
     * @param Booking $booking
     * @param int|null $userId
     * @param string|null $userEmail
     * @return array ['success' => bool, 'record' => EmailHistory]
     */
    public function sendEmailConfirmation(Booking $booking, ?int $userId, ?string $userEmail): array;

    /**
     * Send invoice for a booking.
     *
     * @param Booking $booking
     * @param int|null $userId
     * @param string|null $userEmail
     * @return array ['success' => bool, 'record' => EmailHistory]
     */
    public function sendInvoice(Booking $booking, ?int $userId, ?string $userEmail): array;

    /**
     * Send receipt for a booking.
     *
     * @param Booking $booking
     * @param int|null $userId
     * @param string|null $userEmail
     * @return array ['success' => bool, 'record' => EmailHistory]
     */
    public function sendReceipt(Booking $booking, ?int $userId, ?string $userEmail): array;

    /**
     * Get booking audit history.
     *
     * @param Booking $booking
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAuditHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get booking inventory audit history.
     *
     * @param Booking $booking
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInventoryHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get booking detail audit history.
     *
     * @param Booking $booking
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDetailHistory(Booking $booking, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get stock usages for a booking.
     *
     * @param Booking $booking
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getStockUsages(Booking $booking, int $perPage = 10): LengthAwarePaginator;

    /**
     * Generate invoice PDF for a booking.
     *
     * @param Booking $booking
     * @return mixed PDF response
     */
    public function generateInvoicePdf(Booking $booking);

    /**
     * Generate receipt PDF for a booking.
     *
     * @param Booking $booking
     * @return mixed PDF response
     */
    public function generateReceiptPdf(Booking $booking);
}
