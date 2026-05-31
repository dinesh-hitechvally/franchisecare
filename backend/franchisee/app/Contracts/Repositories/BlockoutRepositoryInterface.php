<?php

namespace App\Contracts\Repositories;

use App\Models\Blockout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BlockoutRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id): ?Blockout;

    public function findByIdOrFail(int $id): Blockout;

    public function create(array $data): Blockout;

    public function update(Blockout $blockout, array $data): Blockout;

    public function delete(Blockout $blockout): bool;

    public function deleteByRecurringId(int $recurringId): int;
}
