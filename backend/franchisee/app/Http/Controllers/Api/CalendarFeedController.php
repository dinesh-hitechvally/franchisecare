<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\CalendarFeedServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarFeedController extends Controller
{
    public function __construct(
        private CalendarFeedServiceInterface $calendarFeedService
    ) {}

    /**
     * Return the unioned booking + blockout events for the current company
     * within a date range, pre-shaped for direct calendar rendering.
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $events = $this->calendarFeedService->getEvents(
            auth()->user()->company_id,
            $validated['date_from'],
            $validated['date_to']
        );

        return response()->json(['data' => $events]);
    }
}
