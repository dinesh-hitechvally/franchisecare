<?php

namespace App\Repositories;

use App\Contracts\Repositories\ServiceInventoryUsageRepositoryInterface;
use App\Models\ServiceInventoryUsage;
use App\Models\BookingInventoryAudit;
use App\Models\InventoryUnitConversion;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ServiceInventoryUsageRepository implements ServiceInventoryUsageRepositoryInterface
{
    public function all(array $filters): Collection
    {
        return $this->buildQuery($filters)->get();
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->buildQuery($filters)->paginate($perPage);
    }

    protected function buildQuery(array $filters)
    {
        $query = ServiceInventoryUsage::with(['service:id,name,category_id', 'unit', 'inventoryItem'])
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

    public function create(array $data): ServiceInventoryUsage
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

        return ServiceInventoryUsage::create($data);
    }

    public function update(ServiceInventoryUsage $usage, array $data): ServiceInventoryUsage
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

    public function delete(ServiceInventoryUsage $usage): void
    {
        $usage->delete();
    }

    public function getHistory(int $serviceId): Collection
    {
        $audits = BookingInventoryAudit::query()
            ->select(
                'booking_inventory_audits.*',
                'bookings.id as booking_id',
                'bookings.appointment_date',
                'bookings.appointment_time',
                'customers.name as customer_name',
                'services.name as service_name',
                DB::raw('COALESCE(booking_services.service_id, services.id) as service_id')
            )
            ->join('bookings', 'booking_inventory_audits.booking_id', '=', 'bookings.id')
            ->join('booking_services', 'bookings.id', '=', 'booking_services.booking_id')
            ->join('services', 'booking_services.service_id', '=', 'services.id')
            ->leftJoin('customers', 'bookings.customer_id', '=', 'customers.id')
            ->where('booking_services.service_id', $serviceId)
            ->orderBy('booking_inventory_audits.created_at', 'desc')
            ->get();

        return $audits->map(function ($audit) {
            $quantityInMl = null;
            $fromUnit = 'pumps';
            $toUnit = 'ml';

            if ($audit->quantity_change) {
                $quantityInMl = InventoryUnitConversion::convert(
                    abs($audit->quantity_change),
                    $fromUnit,
                    $toUnit,
                    $audit->inventory_item_name
                );
            }

            return [
                'id' => $audit->id,
                'booking_id' => $audit->booking_id,
                'service_name' => $audit->service_name,
                'inventory_name' => $audit->inventory_item_name,
                'quantity_change' => $audit->quantity_change,
                'quantity_in_ml' => $quantityInMl,
                'change_type' => $audit->change_type,
                'customer_name' => $audit->customer_name ?? 'N/A',
                'date_time' => $audit->created_at->format('Y-m-d H:i:s'),
                'appointment_date' => $audit->appointment_date,
                'appointment_time' => $audit->appointment_time,
            ];
        });
    }
}
