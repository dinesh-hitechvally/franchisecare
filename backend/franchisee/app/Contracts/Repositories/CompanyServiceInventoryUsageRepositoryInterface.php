<?php

namespace App\Contracts\Repositories;

use App\Models\CompanyServiceInventoryUsage;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyServiceInventoryUsageRepositoryInterface
{
    public function all(int $companyId, array $filters): Collection;
    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): CompanyServiceInventoryUsage;
    public function update(CompanyServiceInventoryUsage $usage, array $data): CompanyServiceInventoryUsage;
    public function delete(CompanyServiceInventoryUsage $usage): void;
}
