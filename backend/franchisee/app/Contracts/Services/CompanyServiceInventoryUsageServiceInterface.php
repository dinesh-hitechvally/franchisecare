<?php

namespace App\Contracts\Services;

use App\Models\CompanyServiceInventoryUsage;
use Illuminate\Support\Collection;

interface CompanyServiceInventoryUsageServiceInterface
{
    public function index(array $filters, ?int $perPage = null): array|Collection;
    public function create(array $data): array;
    public function update(CompanyServiceInventoryUsage $usage, array $data): array;
    public function delete(CompanyServiceInventoryUsage $usage): void;
}
