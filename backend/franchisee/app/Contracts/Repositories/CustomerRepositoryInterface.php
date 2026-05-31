<?php

namespace App\Contracts\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function getAll(array $filters = []): Collection;

    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function findById(int $id, array $relations = []): ?Customer;

    public function findByIdOrFail(int $id, array $relations = []): Customer;

    public function create(array $data): Customer;

    public function update(Customer $customer, array $data): Customer;

    public function delete(Customer $customer): bool;

    public function archive(Customer $customer): Customer;

    public function restore(Customer $customer): Customer;
}
