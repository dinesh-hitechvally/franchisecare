<?php

namespace App\Services;

use App\Contracts\Repositories\CompanyServiceInventoryUsageRepositoryInterface;
use App\Contracts\Services\CompanyServiceInventoryUsageServiceInterface;
use App\Models\CompanyServiceInventoryUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class CompanyServiceInventoryUsageService implements CompanyServiceInventoryUsageServiceInterface
{
    public function __construct(
        protected CompanyServiceInventoryUsageRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): int
    {
        $user = Auth::user();
        return $user?->company_id ?? $user?->franchise_id;
    }

    public function index(array $filters, ?int $perPage = null): array|Collection
    {
        $companyId = $this->getCompanyId();

        if ($perPage) {
            $paginator = $this->repository->paginate($companyId, $filters, $perPage);

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

        return $this->transformCollection($this->repository->all($companyId, $filters));
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
            'company_id' => $item->company_id,
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
        $data['company_id'] = $this->getCompanyId();
        $usage = $this->repository->create($data);
        return $this->transformItem($usage->load(['service:id,name,category_id', 'unit', 'inventoryItem']));
    }

    public function update(CompanyServiceInventoryUsage $usage, array $data): array
    {
        $usage = $this->repository->update($usage, $data);
        return $this->transformItem($usage->load(['service:id,name,category_id', 'unit', 'inventoryItem']));
    }

    public function delete(CompanyServiceInventoryUsage $usage): void
    {
        $this->repository->delete($usage);
    }
}
