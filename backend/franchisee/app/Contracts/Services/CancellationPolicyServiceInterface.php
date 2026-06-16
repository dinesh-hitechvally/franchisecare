<?php

namespace App\Contracts\Services;

use Illuminate\Http\JsonResponse;

interface CancellationPolicyServiceInterface
{
    public function index(): array;
}
