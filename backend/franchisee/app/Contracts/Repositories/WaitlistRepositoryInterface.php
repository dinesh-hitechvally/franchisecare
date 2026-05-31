<?php

namespace App\Contracts\Repositories;

use App\Models\Waitlist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface WaitlistRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?Waitlist;

    public function findByIdOrFail(int $id, array $relations = []): Waitlist;

    public function create(array $data): Waitlist;

    public function update(Waitlist $waitlist, array $data): Waitlist;

    public function delete(Waitlist $waitlist): bool;
}
