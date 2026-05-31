<?php

namespace App\Repositories;

use App\Contracts\Repositories\CalendarEventRepositoryInterface;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CalendarEventRepository implements CalendarEventRepositoryInterface
{
    public function findByDateRange(int $companyId, string $startDate, string $endDate, ?string $eventType = null): Collection
    {
        $query = CalendarEvent::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function($q) use ($startDate, $endDate, $companyId) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhere(function($subQ) use ($startDate, $endDate, $companyId) {
                        $subQ->where('company_id', $companyId)
                            ->where('is_active', true)
                            ->whereBetween('end_date', [$startDate, $endDate]);
                    });
            });

        if ($eventType) {
            $query->where('event_type', $eventType);
        }

        return $query->with('customer', 'booking', 'blockout')->orderBy('start_date')->get();
    }

    public function findByMonth(int $companyId, int $year, int $month): Collection
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->clone()->endOfMonth();

        return CalendarEvent::query()
            ->where('company_id', $companyId)
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
    }

    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::create($data);
    }

    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $event->update($data);
        return $event->fresh();
    }

    public function delete(CalendarEvent $event): void
    {
        $event->delete();
    }

    public function deleteByCompanyId(int $companyId): void
    {
        CalendarEvent::where('company_id', $companyId)->delete();
    }

    public function find(int $id): ?CalendarEvent
    {
        return CalendarEvent::with('customer', 'booking', 'blockout')->find($id);
    }
}
