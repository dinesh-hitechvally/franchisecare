<?php

namespace App\Repositories;

use App\Contracts\Repositories\IntakeFormRepositoryInterface;
use App\Models\CustomerItemWaiver;
use App\Models\CustomerItemWaiverAudit;
use Illuminate\Database\Eloquent\Collection;

class IntakeFormRepository implements IntakeFormRepositoryInterface
{
    public function getByPet(int $petId): Collection
    {
        return CustomerItemWaiver::where('item_id', $petId)
            ->latest()
            ->get();
    }

    public function findById(int $id): ?CustomerItemWaiver
    {
        return CustomerItemWaiver::find($id);
    }

    public function findByIdOrFail(int $id): CustomerItemWaiver
    {
        return CustomerItemWaiver::findOrFail($id);
    }

    public function create(array $data): CustomerItemWaiver
    {
        return CustomerItemWaiver::create($data);
    }

    public function updateOrCreate(array $conditions, array $data): CustomerItemWaiver
    {
        return CustomerItemWaiver::updateOrCreate($conditions, $data);
    }

    public function createAudit(array $data): void
    {
        CustomerItemWaiverAudit::create($data);
    }

    public function getHistory(int $petId, string $type): Collection
    {
        return CustomerItemWaiverAudit::where('item_id', $petId)
            ->where('waiver_type', $type)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->get();
    }
}
