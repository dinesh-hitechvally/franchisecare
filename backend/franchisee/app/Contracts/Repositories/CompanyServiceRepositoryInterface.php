<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;

interface CompanyServiceRepositoryInterface
{
    public function all(int $companyId): Collection;
    public function updateAll(int $companyId, array $services): void;
}
