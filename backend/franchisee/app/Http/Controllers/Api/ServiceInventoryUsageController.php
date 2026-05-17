<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceInventoryUsage;
use App\Models\BookingInventoryAudit;
use App\Models\InventoryUnitConversion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ServiceInventoryUsageController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceInventoryUsage::with(['service:id,name,category_id', 'unit'])
            ->orderBy('inventory_name')
            ->orderByDesc('id');

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('inventory_name', 'like', $term)
                    ->orWhereHas('unit', function ($unitQuery) use ($term) {
                        $unitQuery->where('name', 'like', $term);
                    })
                    ->orWhereHas('service', function ($serviceQuery) use ($term) {
                        $serviceQuery->where('name', 'like', $term);
                    });
            });
        }

        if ($request->filled('page')) {
            $perPage = max(1, min((int) $request->input('per_page', 25), 100));
            $page = max(1, (int) $request->input('page'));
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'inventory_name' => 'required|string|max:255',
            'quantity_per_booking' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();
        $validated['company_id'] = $user?->company_id ?? $user?->franchise_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Convert unit name to unit_id
        $unit = \App\Models\Unit::where('name', $validated['unit'])->first();
        if ($unit) {
            $validated['unit_id'] = $unit->id;
        }
        unset($validated['unit']);

        $usage = ServiceInventoryUsage::create($validated);

        return response()->json($usage->load(['service:id,name,category_id', 'unit']), 201);
    }

    public function update(Request $request, ServiceInventoryUsage $serviceInventoryUsage)
    {
        $validated = $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'inventory_name' => 'sometimes|string|max:255',
            'quantity_per_booking' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Convert unit name to unit_id if provided
        if (isset($validated['unit'])) {
            $unit = \App\Models\Unit::where('name', $validated['unit'])->first();
            if ($unit) {
                $validated['unit_id'] = $unit->id;
            }
            unset($validated['unit']);
        }

        $serviceInventoryUsage->update($validated);

        return response()->json($serviceInventoryUsage->load(['service:id,name,category_id', 'unit']));
    }

    public function destroy(ServiceInventoryUsage $serviceInventoryUsage)
    {
        $serviceInventoryUsage->delete();

        return response()->json(null, 204);
    }

    /**
     * Get inventory usage history for a specific service
     */
    public function history(Request $request, $serviceId)
    {
        $query = BookingInventoryAudit::query()
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
            ->orderBy('booking_inventory_audits.created_at', 'desc');

        $audits = $query->get();

        // Convert quantities to ml
        $audits = $audits->map(function ($audit) {
            $quantityInMl = null;

            // Assuming quantity_change is stored in the unit from service_inventory_usages (pumps)
            // We need to find the unit from the audit or assume 'pumps'
            $fromUnit = 'pumps'; // Default unit
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

        return response()->json($audits);
    }
}