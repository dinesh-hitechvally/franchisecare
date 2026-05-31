<?php

namespace App\Contracts\Repositories;

use App\Models\CalendarEvent;
use Illuminate\Support\Collection;

interface CalendarEventRepositoryInterface
{
    public function findByDateRange(int $companyId, string $startDate, string $endDate, ?string $eventType = null): Collection;
    public function findByMonth(int $companyId, int $year, int $month): Collection;
    public function create(array $data): CalendarEvent;
    public function update(CalendarEvent $event, array $data): CalendarEvent;
    public function delete(CalendarEvent $event): void;
    public function deleteByCompanyId(int $companyId): void;
    public function find(int $id): ?CalendarEvent;
}
