<?php

namespace App\Contracts\Repositories;

use App\Models\Income;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface IncomeRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?Income;

    public function findByIdOrFail(int $id, array $relations = []): Income;

    public function create(array $data): Income;

    public function update(Income $income, array $data): Income;

    public function delete(Income $income): bool;
}
