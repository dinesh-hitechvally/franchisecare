<?php

namespace App\Contracts\Services;

use App\Models\User;

interface DashboardServiceInterface
{
    public function getMetrics(User $user): array;
    public function getActivities(User $user, int $limit = 10): array;
    public function getNews(?int $companyId): array;
    public function getBookingSchedule(?int $companyId, int $days = 5): array;
    public function getForecast(?int $companyId, int $weeks = 12): array;
}
