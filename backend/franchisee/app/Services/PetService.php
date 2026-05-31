<?php

namespace App\Services;

use App\Contracts\Repositories\PetRepositoryInterface;
use App\Contracts\Services\PetServiceInterface;
use App\Models\CustomerItem;
use App\Models\CustomerItemAudit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class PetService implements PetServiceInterface
{
    public function __construct(
        private PetRepositoryInterface $petRepository
    ) {}

    public function listPets(): Collection
    {
        return $this->petRepository->getAll();
    }

    public function getPet(int $id): CustomerItem
    {
        return $this->petRepository->findByIdOrFail($id);
    }

    public function getPetsByCustomer(int $customerId): Collection
    {
        return $this->petRepository->findByCustomer($customerId);
    }

    public function createPet(array $data, $imageFile = null): CustomerItem
    {
        if ($imageFile) {
            $path = $imageFile->store('pet-images', 'public');
            $data['image_path'] = $path;
        }

        $pet = $this->petRepository->create($data);

        // Create audit record
        $this->createAuditRecord($pet, 'created');

        return $pet;
    }

    public function updatePet(CustomerItem $pet, array $data, $imageFile = null, bool $removeImage = false): CustomerItem
    {
        if ($imageFile) {
            // Delete old image if exists
            if ($pet->image_path) {
                Storage::disk('public')->delete($pet->image_path);
            }
            $path = $imageFile->store('pet-images', 'public');
            $data['image_path'] = $path;
        } elseif ($removeImage && $pet->image_path) {
            Storage::disk('public')->delete($pet->image_path);
            $data['image_path'] = null;
        }

        $pet = $this->petRepository->update($pet, $data);

        // Create audit record
        $this->createAuditRecord($pet, 'updated');

        return $pet;
    }

    public function deletePet(CustomerItem $pet): bool
    {
        // Delete image if exists
        if ($pet->image_path) {
            Storage::disk('public')->delete($pet->image_path);
        }

        // Create audit record before deletion
        $this->createAuditRecord($pet, 'deleted');

        return $this->petRepository->delete($pet);
    }

    public function getPetHistory(CustomerItem $pet): LengthAwarePaginator
    {
        return CustomerItemAudit::where('item_id', $pet->id)
            ->orderByDesc('changed_at')
            ->paginate(10);
    }

    private function createAuditRecord(CustomerItem $pet, string $action): void
    {
        CustomerItemAudit::create([
            'item_id' => $pet->id,
            'customer_id' => $pet->customer_id,
            'action' => $action,
            'old_values' => $action !== 'created' ? $pet->getOriginal() : null,
            'new_values' => $pet->toArray(),
            'changed_at' => now(),
            'changed_by' => auth()->id(),
        ]);
    }
}
