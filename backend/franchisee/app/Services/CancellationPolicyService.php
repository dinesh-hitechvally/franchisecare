<?php

namespace App\Services;

use App\Contracts\Services\CancellationPolicyServiceInterface;
use Illuminate\Support\Facades\DB;

class CancellationPolicyService implements CancellationPolicyServiceInterface
{
    public function index(): array
    {
        return DB::table('cancellation_policies')->get()->toArray();
    }
}
