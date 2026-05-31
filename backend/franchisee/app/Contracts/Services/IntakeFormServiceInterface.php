<?php

namespace App\Contracts\Services;

use App\Models\CustomerItemWaiver;
use App\Models\CustomerItem;
use Illuminate\Database\Eloquent\Collection;

interface IntakeFormServiceInterface
{
    public function getWaiversByPet(CustomerItem $pet): Collection;

    public function getWaiver(int $id): CustomerItemWaiver;

    public function getWaiverHistory(CustomerItem $pet, string $type): Collection;

    public function createWaiver(array $data): array;
}
