<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    /**
     * Display calendar events for a date range
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $query = CalendarEvent::query()
            ->where('company_id', $validated['company_id'])
            ->where('is_active', true)
            ->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
            ->orWhere(function($q) use ($validated) {
                $q->where('company_id', $validated['company_id'])
                    ->where('is_active', true)
                    ->whereBetween('end_date', [$validated['start_date'], $validated['end_date']]);
            });

        if ($request->has('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        return response()->json($query->with('customer', 'booking', 'blockout')->orderBy('start_date')->get());
    }

    /**
     * Get calendar events by month
     */
    public function getByMonth(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $startDate = \Carbon\Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth();
        $endDate = $startDate->clone()->endOfMonth();

        $events = CalendarEvent::query()
            ->where('company_id', $validated['company_id'])
            ->where('is_active', true)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function($subQ) use ($startDate, $endDate) {
                        $subQ->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                    });
            })
            ->with('customer', 'booking', 'blockout')
            ->orderBy('start_date')
            ->get();

        return response()->json($events);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'event_type' => 'required|in:booking,blockout',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_date' => 'required|date',
            'end_time' => 'required|string',
            'color' => 'nullable|string',
            'location' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'blockout_id' => 'nullable|exists:blockouts,id',
            'is_recurring' => 'boolean',
        ]);

        $validated['start_time'] = $this->convertTo24Hour($validated['start_time']);
        $validated['end_time'] = $this->convertTo24Hour($validated['end_time']);

        $event = CalendarEvent::create($validated);

        return response()->json($event, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CalendarEvent $calendarEvent)
    {
        return response()->json($calendarEvent->load('customer', 'booking', 'blockout'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CalendarEvent $calendarEvent)
    {
        $validated = $request->validate([
            'event_type' => 'sometimes|in:booking,blockout',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'start_time' => 'sometimes|string',
            'end_date' => 'sometimes|date',
            'end_time' => 'sometimes|string',
            'color' => 'nullable|string',
            'location' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'is_recurring' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $calendarEvent->update($validated);

        return response()->json($calendarEvent);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CalendarEvent $calendarEvent)
    {
        $calendarEvent->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync calendar events from bookings and blockouts
     */
    public function syncEvents(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = $validated['company_id'];

        // Clear existing events for this company
        CalendarEvent::where('company_id', $companyId)->delete();

        // Sync bookings
        $bookings = \App\Models\Booking::where('company_id', $companyId)->get();
        foreach ($bookings as $booking) {
            CalendarEvent::create([
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
        $blockouts = \App\Models\Blockout::where('company_id', $companyId)->get();
        foreach ($blockouts as $blockout) {
            CalendarEvent::create([
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
                'is_recurring' => $blockout->is_recurring,
                'is_active' => $blockout->active,
            ]);
        }

        return response()->json(['message' => 'Calendar events synced successfully']);
    }

    private function convertTo24Hour($time)
    {
        if (!$time) return '00:00:00';
        try {
            return \Carbon\Carbon::createFromFormat('h:i A', $time)->format('H:i:s');
        } catch (\Exception $e) {
            return $time;
        }
    }
}
