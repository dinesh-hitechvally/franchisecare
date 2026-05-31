<?php

namespace App\Contracts\Services;

use App\Models\User;

interface BenchmarkingServiceInterface
{
    public function getReport(User $user, array $filters): array;
}
