<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\WaitlistServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Waitlist\StoreWaitlistRequest;
use App\Http\Requests\Waitlist\UpdateStatusRequest;
use App\Http\Requests\Waitlist\UpdateWaitlistRequest;
use App\Models\Waitlist;
use App\Services\WaitlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID WaitlistController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on WaitlistServiceInterface abstraction
 */
class WaitlistController extends Controller
{
    public function __construct(
        private WaitlistServiceInterface $waitlistService
    ) {}

    /**
     * Display a listing of waitlists.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->get('status'),
            'customer_id' => $request->get('customer_id'),
            'date_from' => $request->get('dateFrom'),
            'date_to' => $request->get('dateTo'),
            'search' => $request->get('search'),
        ];

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        if ($request->filled('page')) {
            $paginator = $this->waitlistService->listWaitlists($filters, $perPage);

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

        // Get all without pagination
        $waitlists = $this->waitlistService->listWaitlists($filters, 1000);

        return response()->json($waitlists->items());
    }

    /**
     * Store a newly created waitlist.
     */
    public function store(StoreWaitlistRequest $request): JsonResponse
    {
        $waitlist = $this->waitlistService->createWaitlist($request->waitlistData());

        return response()->json($waitlist, 201);
    }

    /**
     * Display the specified waitlist.
     */
    public function show(Waitlist $waitlist): JsonResponse
    {
        return response()->json($waitlist->load(['customer', 'details.item', 'details.service']));
    }

    /**
     * Update the specified waitlist.
     */
    public function update(UpdateWaitlistRequest $request, Waitlist $waitlist): JsonResponse
    {
        $waitlist = $this->waitlistService->updateWaitlist($waitlist, $request->waitlistData());

        return response()->json($waitlist);
    }

    /**
     * Update only status.
     */
    public function updateStatus(UpdateStatusRequest $request, Waitlist $waitlist): JsonResponse
    {
        $waitlist = $this->waitlistService->updateStatus($waitlist, $request->status);

        return response()->json($waitlist);
    }

    /**
     * Convert waitlist to a real booking.
     */
    public function convertToBooking(Waitlist $waitlist): JsonResponse
    {
        $result = $this->waitlistService->convertToBooking($waitlist);

        return response()->json([
            'message' => 'Waitlist converted to booking successfully.',
            'booking' => $result['booking'],
            'waitlist' => $result['waitlist'],
        ]);
    }

    /**
     * Send email confirmation for a waitlist.
     */
    public function sendEmailConfirmation(Waitlist $waitlist): JsonResponse
    {
        $this->waitlistService->sendEmailConfirmation($waitlist);

        return response()->json(['message' => 'Email confirmation sent successfully.']);
    }

    /**
     * Get audit history for a waitlist.
     */
    public function getHistory(Waitlist $waitlist): JsonResponse
    {
        $history = $this->waitlistService->getWaitlistHistory($waitlist);

        return response()->json($history);
    }

    /**
     * Remove a waitlist.
     */
    public function destroy(Waitlist $waitlist): JsonResponse
    {
        $this->waitlistService->deleteWaitlist($waitlist);

        return response()->json(null, 204);
    }
}
