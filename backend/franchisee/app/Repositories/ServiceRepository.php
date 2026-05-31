<?php

namespace App\Repositories;

use App\Contracts\Repositories\ServiceRepositoryInterface;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function getAll(array $filters = []): Collection
    {
        $query = Service::query();

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->orderBy('name')->get();
    }

    public function findById(int $id): ?Service
    {
        return Service::find($id);
    }

    public function findByIdOrFail(int $id): Service
    {
        return Service::findOrFail($id);
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);
        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        return $service->delete();
    }
}
