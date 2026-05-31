<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\CalendarEventServiceInterface;
use App\Http\Requests\CalendarEvent\IndexCalendarEventRequest;
use App\Http\Requests\CalendarEvent\GetByMonthCalendarEventRequest;
use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Http\Requests\CalendarEvent\SyncCalendarEventRequest;
use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;

class CalendarEventController extends Controller
{
    public function __construct(
        protected CalendarEventServiceInterface $calendarEventService
    ) {}

    public function index(IndexCalendarEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $events = $this->calendarEventService->index(
            (int) $validated['company_id'],
            $validated['start_date'],
            $validated['end_date'],
            $validated['event_type'] ?? null
        );

        return response()->json($events);
    }

    public function getByMonth(GetByMonthCalendarEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $events = $this->calendarEventService->getByMonth(
            (int) $validated['company_id'],
            (int) $validated['year'],
            (int) $validated['month']
        );

        return response()->json($events);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $event = $this->calendarEventService->create($request->validated());
        return response()->json($event, 201);
    }

    public function show(CalendarEvent $calendarEvent): JsonResponse
    {
        return response()->json($this->calendarEventService->show($calendarEvent));
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $event = $this->calendarEventService->update($calendarEvent, $request->validated());
        return response()->json($event);
    }

    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $this->calendarEventService->delete($calendarEvent);
        return response()->json(null, 204);
    }

    public function syncEvents(SyncCalendarEventRequest $request): JsonResponse
    {
        $result = $this->calendarEventService->syncEvents((int) $request->validated('company_id'));
        return response()->json($result);
    }
}
