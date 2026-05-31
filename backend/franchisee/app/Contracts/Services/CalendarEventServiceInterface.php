<?php

namespace App\Contracts\Services;

use App\Models\CalendarEvent;
use Illuminate\Support\Collection;

interface CalendarEventServiceInterface
{
    public function index(int $companyId, string $startDate, string $endDate, ?string $eventType = null): Collection;
    public function getByMonth(int $companyId, int $year, int $month): Collection;
    public function create(array $data): CalendarEvent;
    public function show(CalendarEvent $event): CalendarEvent;
    public function update(CalendarEvent $event, array $data): CalendarEvent;
    public function delete(CalendarEvent $event): void;
    public function syncEvents(int $companyId): array;
}
