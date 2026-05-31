<?php

namespace App\Contracts\Services;

use Illuminate\Support\Collection;

interface CompanyServiceServiceInterface
{
    public function all(): Collection;
    public function updateAll(array $services): Collection;
}
