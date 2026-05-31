<?php

namespace App\Contracts\Services;

use App\Models\CustomerItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PetServiceInterface
{
    public function listPets(): Collection;

    public function getPet(int $id): CustomerItem;

    public function getPetsByCustomer(int $customerId): Collection;

    public function createPet(array $data, $imageFile = null): CustomerItem;

    public function updatePet(CustomerItem $pet, array $data, $imageFile = null, bool $removeImage = false): CustomerItem;

    public function deletePet(CustomerItem $pet): bool;

    public function getPetHistory(CustomerItem $pet): LengthAwarePaginator;
}
