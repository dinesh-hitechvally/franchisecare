<?php

namespace App\Services;

use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Contracts\Services\ServiceServiceInterface;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceService implements ServiceServiceInterface
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository
    ) {}

    public function listServices(array $filters = []): Collection
    {
        return $this->serviceRepository->getAll($filters);
    }

    public function getService(int $id): Service
    {
        return $this->serviceRepository->findByIdOrFail($id);
    }

    public function createService(array $data): Service
    {
        return $this->serviceRepository->create($data);
    }

    public function updateService(Service $service, array $data): Service
    {
        return $this->serviceRepository->update($service, $data);
    }

    public function deleteService(Service $service): bool
    {
        return $this->serviceRepository->delete($service);
    }
}
