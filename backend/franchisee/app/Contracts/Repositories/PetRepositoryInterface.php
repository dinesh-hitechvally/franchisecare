<?php

namespace App\Contracts\Repositories;

use App\Models\CustomerItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PetRepositoryInterface
{
    public function getAll(): Collection;

    public function findById(int $id): ?CustomerItem;

    public function findByIdOrFail(int $id): CustomerItem;

    public function findByCustomer(int $customerId): Collection;

    public function create(array $data): CustomerItem;

    public function update(CustomerItem $pet, array $data): CustomerItem;

    public function delete(CustomerItem $pet): bool;
}
