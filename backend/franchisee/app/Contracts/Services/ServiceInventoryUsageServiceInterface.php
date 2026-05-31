<?php

namespace App\Contracts\Services;

use App\Models\ServiceInventoryUsage;
use Illuminate\Support\Collection;

interface ServiceInventoryUsageServiceInterface
{
    public function index(array $filters, ?int $perPage = null): array|Collection;
    public function create(array $data): array;
    public function update(ServiceInventoryUsage $usage, array $data): array;
    public function delete(ServiceInventoryUsage $usage): void;
    public function getHistory(int $serviceId): Collection;
}
