<?php

namespace App\Contracts\Services;

use App\Models\User;

interface ReportServiceInterface
{
    public function tracking(User $user, int $year): array;
    public function gstSummary(User $user, string $dateFrom, string $dateTo): array;
    public function profitLoss(User $user, string $dateFrom, string $dateTo): array;
}
