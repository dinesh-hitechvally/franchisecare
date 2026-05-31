<?php

namespace App\Contracts\Services;

use App\Models\User;

interface UnbookedCustomerReportServiceInterface
{
    public function index(User $user, array $filters): array;
}
