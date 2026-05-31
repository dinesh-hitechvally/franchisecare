<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\BookingServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\RebookRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Requests\Booking\UpdateStatusRequest;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID Principles Implementation:
 * 
 * Single Responsibility Principle (SRP):
 * - This controller ONLY handles HTTP concerns (request/response)
 * - Business logic is delegated to BookingService
 * - Validation is handled by Form Request classes
 * - Data access is handled by BookingRepository (via service)
 * 
 * Open/Closed Principle (OCP):
 * - New functionality can be added via new service methods
 * - No need to modify existing controller methods
 * 
 * Dependency Inversion Principle (DIP):
 * - Controller depends on BookingServiceInterface (abstraction)
 * - Not on concrete BookingService implementation
 */
class BookingController extends Controller
{
    public function __construct(
        protected BookingServiceInterface $bookingService
    ) {}

    /**
     * Display a listing of bookings.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'company_id' => auth()->user()?->company_id,
            'status' => $request->input('status'),
            'customer_id' => $request->input('customer_id'),
            'dateFrom' => $request->input('dateFrom'),
            'dateTo' => $request->input('dateTo'),
            'search' => $request->input('search'),
        ];

        $filters = array_filter($filters, fn($v) => $v !== null);

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        if ($request->filled('page')) {
            $page = max(1, (int) $request->input('page'));
            $paginator = $this->bookingService->listBookings($filters, true, $perPage, $page);

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

        return response()->json($this->bookingService->listBookings($filters));
    }

    /**
     * Store a newly created booking.
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBooking(
            $request->bookingData(),
            $request->servicesData(),
            auth()->user()->company_id
        );

        return response()->json($booking, 201);
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update the specified booking.
     */
    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        $updatedBooking = $this->bookingService->updateBooking(
            $booking,
            $request->bookingData(),
            $request->servicesData()
        );

        return response()->json($updatedBooking);
    }

    /**
     * Update booking status only.
     */
    public function updateStatus(UpdateStatusRequest $request, Booking $booking): JsonResponse
    {
        $updatedBooking = $this->bookingService->updateBookingStatus(
            $booking,
            $request->validated()['status']
        );

        return response()->json($updatedBooking);
    }

    /**
     * Remove the specified booking.
     */
    public function destroy(Booking $booking): JsonResponse
    {
        $this->bookingService->deleteBooking($booking);
        return response()->json(null, 204);
    }

    /**
     * Rebook an existing booking to a new date/time.
     */
    public function rebook(RebookRequest $request, Booking $booking): JsonResponse
    {
        $rebookedBooking = $this->bookingService->rebookBooking(
            $booking,
            $request->dateTimeData(),
            auth()->user()->company_id
        );

        return response()->json($rebookedBooking, 201);
    }

    /**
     * Get booking audit history.
     */
    public function getHistory(Booking $booking): JsonResponse
    {
        return response()->json($this->bookingService->getAuditHistory($booking));
    }

    /**
     * Get booking inventory audit history.
     */
    public function getInventoryHistory(Booking $booking): JsonResponse
    {
        return response()->json($this->bookingService->getInventoryHistory($booking));
    }

    /**
     * Get booking detail audit history.
     */
    public function getDetailHistory(Booking $booking): JsonResponse
    {
        return response()->json($this->bookingService->getDetailHistory($booking));
    }

    /**
     * Get stock usages for a booking.
     */
    public function getStockUsages(Booking $booking): JsonResponse
    {
        return response()->json($this->bookingService->getStockUsages($booking));
    }

    /**
     * Generate invoice PDF.
     */
    public function generateInvoice(Request $request, Booking $booking)
    {
        // Verify token from query parameter for browser access
        $token = $request->query('token');
        if ($token) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$tokenModel || !$tokenModel->tokenable) {
                abort(401, 'Invalid or expired token');
            }
        } elseif (!$request->user()) {
            abort(401, 'Authentication required');
        }

        $pdf = $this->bookingService->generateInvoicePdf($booking);
        return $pdf->download("invoice-{$booking->id}.pdf");
    }

    /**
     * Generate receipt PDF.
     */
    public function generateReceipt(Request $request, Booking $booking)
    {
        // Verify token from query parameter for browser access
        $token = $request->query('token');
        if ($token) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (!$tokenModel || !$tokenModel->tokenable) {
                abort(401, 'Invalid or expired token');
            }
        } elseif (!$request->user()) {
            abort(401, 'Authentication required');
        }

        $pdf = $this->bookingService->generateReceiptPdf($booking);
        return $pdf->download("receipt-{$booking->id}.pdf");
    }

    /**
     * Send invoice email.
     */
    public function sendInvoice(Booking $booking, Request $request): JsonResponse
    {
        $result = $this->bookingService->sendInvoice(
            $booking,
            $request->user()?->id,
            $request->user()?->email
        );

        return response()->json([
            'message' => 'Invoice sent successfully.',
            'data' => $result['record'],
        ], 201);
    }

    /**
     * Send receipt email.
     */
    public function sendReceipt(Booking $booking, Request $request): JsonResponse
    {
        $result = $this->bookingService->sendReceipt(
            $booking,
            $request->user()?->id,
            $request->user()?->email
        );

        return response()->json([
            'message' => 'Receipt sent successfully.',
            'data' => $result['record'],
        ], 201);
    }

    /**
     * Send SMS confirmation.
     */
    public function sendSmsConfirmation(Booking $booking, Request $request): JsonResponse
    {
        $result = $this->bookingService->sendSmsConfirmation(
            $booking,
            $request->user()?->id,
            $request->user()?->company_id
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                'data' => $result['record'],
            ], 500);
        }

        return response()->json([
            'message' => 'SMS confirmation sent successfully.',
            'data' => $result['record'],
        ], 201);
    }

    /**
     * Send email confirmation.
     */
    public function sendEmailConfirmation(Booking $booking, Request $request): JsonResponse
    {
        $result = $this->bookingService->sendEmailConfirmation(
            $booking,
            $request->user()?->id,
            $request->user()?->email
        );

        return response()->json([
            'message' => 'Email confirmation sent successfully.',
            'data' => $result['record'],
        ], 201);
    }
}
