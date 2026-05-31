<?php

namespace App\Contracts\Services;

use App\Models\User;

interface IncomeReportServiceInterface
{
    public function index(User $user, array $filters): array;
}
