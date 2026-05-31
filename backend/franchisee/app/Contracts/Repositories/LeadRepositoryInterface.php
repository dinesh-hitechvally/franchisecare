<?php

namespace App\Contracts\Repositories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeadRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id): ?Lead;

    public function findByIdOrFail(int $id): Lead;

    public function create(array $data): Lead;

    public function update(Lead $lead, array $data): Lead;

    public function delete(Lead $lead): bool;
}
