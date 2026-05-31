<?php

namespace App\Contracts\Services;

use App\Models\User;

interface BookingReportServiceInterface
{
    public function index(User $user, string $dateFrom, string $dateTo): array;
}
