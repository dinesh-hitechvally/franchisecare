<?php

namespace App\Contracts\Services;

interface CalendarFeedServiceInterface
{
    /**
     * Get the unioned booking + blockout events for a company within a date range,
     * pre-shaped for direct calendar rendering.
     */
    public function getEvents(int $companyId, string $dateFrom, string $dateTo): array;
}
