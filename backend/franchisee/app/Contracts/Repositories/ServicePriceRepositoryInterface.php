<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;

interface ServicePriceRepositoryInterface
{
    public function all(int $companyId): Collection;
    public function updateAll(int $companyId, array $services): Collection;
}
