<?php

namespace App\Contracts\Repositories;

use App\Models\ServiceInventoryUsage;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceInventoryUsageRepositoryInterface
{
    public function all(array $filters): Collection;
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): ServiceInventoryUsage;
    public function update(ServiceInventoryUsage $usage, array $data): ServiceInventoryUsage;
    public function delete(ServiceInventoryUsage $usage): void;
    public function getHistory(int $serviceId): Collection;
}
