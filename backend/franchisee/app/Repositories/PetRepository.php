<?php

namespace App\Repositories;

use App\Contracts\Repositories\PetRepositoryInterface;
use App\Models\CustomerItem;
use Illuminate\Database\Eloquent\Collection;

class PetRepository implements PetRepositoryInterface
{
    public function getAll(): Collection
    {
        return CustomerItem::latest()->get();
    }

    public function findById(int $id): ?CustomerItem
    {
        return CustomerItem::find($id);
    }

    public function findByIdOrFail(int $id): CustomerItem
    {
        return CustomerItem::findOrFail($id);
    }

    public function findByCustomer(int $customerId): Collection
    {
        return CustomerItem::where('customer_id', $customerId)->latest()->get();
    }

    public function create(array $data): CustomerItem
    {
        return CustomerItem::create($data);
    }

    public function update(CustomerItem $pet, array $data): CustomerItem
    {
        $pet->update($data);
        return $pet->fresh();
    }

    public function delete(CustomerItem $pet): bool
    {
        return $pet->delete();
    }
}
