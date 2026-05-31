<?php

namespace App\Contracts\Services;

use App\Models\User;

interface SuburbReportServiceInterface
{
    public function index(User $user, array $filters): array;
}
