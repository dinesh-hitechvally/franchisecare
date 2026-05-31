<?php

namespace App\Contracts\Repositories;

use App\Models\CustomerItemWaiver;
use App\Models\CustomerItem;
use Illuminate\Database\Eloquent\Collection;

interface IntakeFormRepositoryInterface
{
    public function getByPet(int $petId): Collection;

    public function findById(int $id): ?CustomerItemWaiver;

    public function findByIdOrFail(int $id): CustomerItemWaiver;

    public function create(array $data): CustomerItemWaiver;

    public function updateOrCreate(array $conditions, array $data): CustomerItemWaiver;

    public function createAudit(array $data): void;

    public function getHistory(int $petId, string $type): Collection;
}
