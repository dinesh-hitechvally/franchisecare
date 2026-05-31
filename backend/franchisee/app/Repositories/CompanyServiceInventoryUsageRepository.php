<?php

namespace App\Repositories;

use App\Contracts\Repositories\CompanyServiceInventoryUsageRepositoryInterface;
use App\Models\CompanyServiceInventoryUsage;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyServiceInventoryUsageRepository implements CompanyServiceInventoryUsageRepositoryInterface
{
    public function all(int $companyId, array $filters): Collection
    {
        return $this->buildQuery($companyId, $filters)->get();
    }

    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->buildQuery($companyId, $filters)->paginate($perPage);
    }

    protected function buildQuery(int $companyId, array $filters)
    {
        $query = CompanyServiceInventoryUsage::with(['service:id,name,category_id', 'unit', 'inventoryItem'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if (!empty($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($builder) use ($term) {
                $builder->whereHas('inventoryItem', function ($itemQuery) use ($term) {
                        $itemQuery->where('name', 'like', $term);
                    })
                    ->orWhereHas('unit', function ($unitQuery) use ($term) {
                        $unitQuery->where('name', 'like', $term);
                    })
                    ->orWhereHas('service', function ($serviceQuery) use ($term) {
                        $serviceQuery->where('name', 'like', $term);
                    });
            });
        }

        return $query;
    }

    public function create(array $data): CompanyServiceInventoryUsage
    {
        if (isset($data['unit']) && is_string($data['unit'])) {
            $unit = Unit::firstOrCreate(
                ['name' => $data['unit']],
                ['abbreviation' => strtolower(substr($data['unit'], 0, 3))]
            );
            $data['unit_id'] = $unit->id;
            unset($data['unit']);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        return CompanyServiceInventoryUsage::create($data);
    }

    public function update(CompanyServiceInventoryUsage $usage, array $data): CompanyServiceInventoryUsage
    {
        if (isset($data['unit']) && is_string($data['unit'])) {
            $unit = Unit::firstOrCreate(
                ['name' => $data['unit']],
                ['abbreviation' => strtolower(substr($data['unit'], 0, 3))]
            );
            $data['unit_id'] = $unit->id;
            unset($data['unit']);
        }

        $usage->update($data);
        return $usage;
    }

    public function delete(CompanyServiceInventoryUsage $usage): void
    {
        $usage->delete();
    }
}
