<?php

namespace App\Contracts\Services;

use Illuminate\Contracts\Auth\Authenticatable;

interface SmsCreditServiceInterface
{
    public function index(Authenticatable $user): array;

    public function purchase(Authenticatable $user, array $data): array;

    public function history(Authenticatable $user): array;
}
