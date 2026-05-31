<?php

namespace App\Services;

use App\Contracts\Repositories\ServiceInventoryUsageRepositoryInterface;
use App\Contracts\Services\ServiceInventoryUsageServiceInterface;
use App\Models\ServiceInventoryUsage;
use Illuminate\Support\Collection;

class ServiceInventoryUsageService implements ServiceInventoryUsageServiceInterface
{
    public function __construct(
        protected ServiceInventoryUsageRepositoryInterface $repository
    ) {}

    public function index(array $filters, ?int $perPage = null): array|Collection
    {
        if ($perPage) {
            $paginator = $this->repository->paginate($filters, $perPage);

            return [
                'data' => $this->transformCollection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ];
        }

        return $this->transformCollection($this->repository->all($filters));
    }

    protected function transformCollection($items): Collection
    {
        return collect($items)->map(function ($item) {
            return $this->transformItem($item);
        });
    }

    protected function transformItem($item): array
    {
        return [
            'id' => $item->id,
            'service_id' => $item->service_id,
            'inventory_id' => $item->inventory_id,
            'inventory_name' => $item->inventoryItem?->name,
            'quantity_per_booking' => $item->quantity_per_booking,
            'unit_id' => $item->unit_id,
            'unit' => $item->unit,
            'notes' => $item->notes,
            'is_active' => $item->is_active,
            'service' => $item->service,
            'inventory_item' => $item->inventoryItem,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    public function create(array $data): array
    {
        $usage = $this->repository->create($data);
        return $this->transformItem($usage->load(['service:id,name,category_id', 'unit', 'inventoryItem']));
    }

    public function update(ServiceInventoryUsage $usage, array $data): array
    {
        $usage = $this->repository->update($usage, $data);
        return $this->transformItem($usage->load(['service:id,name,category_id', 'unit', 'inventoryItem']));
    }

    public function delete(ServiceInventoryUsage $usage): void
    {
        $this->repository->delete($usage);
    }

    public function getHistory(int $serviceId): Collection
    {
        return $this->repository->getHistory($serviceId);
    }
}
