<?php

namespace App\Contracts\Services;

use App\Models\User;

interface CustomerReportServiceInterface
{
    public function index(User $user, array $filters): array;
}
