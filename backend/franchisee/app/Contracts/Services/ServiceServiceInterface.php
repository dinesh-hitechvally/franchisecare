<?php

namespace App\Contracts\Services;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

interface ServiceServiceInterface
{
    public function listServices(array $filters = []): Collection;

    public function getService(int $id): Service;

    public function createService(array $data): Service;

    public function updateService(Service $service, array $data): Service;

    public function deleteService(Service $service): bool;
}
