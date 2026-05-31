<?php

namespace App\Http\Controllers\Api\Backup;

use App\Http\Controllers\Controller;
use App\Models\CompanyServiceInventoryUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CompanyServiceInventoryUsageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user?->company_id ?? $user?->franchise_id;

        $query = CompanyServiceInventoryUsage::with(['service:id,name,category_id', 'unit', 'inventoryItem'])
            ->where('company_id', $companyId)
            ->orderByDesc('id');

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
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

        if ($request->filled('page')) {
            $perPage = max(1, min((int) $request->input('per_page', 25), 100));
            $page = max(1, (int) $request->input('page'));
            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => $this->transformCollection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        }

        return response()->json($this->transformCollection($query->get()));
    }

    private function transformCollection($items)
    {
        return collect($items)->map(function ($item) {
            return $this->transformItem($item);
        });
    }

    private function transformItem($item)
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

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user?->company_id ?? $user?->franchise_id;

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'inventory_id' => 'required|exists:inventory_items,id',
            'quantity_per_booking' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // Convert unit name to unit_id - create unit if it doesn't exist
        $unit = \App\Models\Unit::firstOrCreate(
            ['name' => $validated['unit']],
            ['abbreviation' => strtolower(substr($validated['unit'], 0, 3))]
        );
        $validated['unit_id'] = $unit->id;
        unset($validated['unit']);

        $usage = CompanyServiceInventoryUsage::create($validated);

        return response()->json($this->transformItem($usage->load(['service:id,name,category_id', 'unit', 'inventoryItem'])), 201);
    }

    public function update(Request $request, CompanyServiceInventoryUsage $companyServiceInventoryUsage)
    {
        $validated = $request->validate([
            'service_id' => 'sometimes|exists:services,id',
            'inventory_id' => 'sometimes|exists:inventory_items,id',
            'quantity_per_booking' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Convert unit name to unit_id if provided - create unit if it doesn't exist
        if (isset($validated['unit'])) {
            $unit = \App\Models\Unit::firstOrCreate(
                ['name' => $validated['unit']],
                ['abbreviation' => strtolower(substr($validated['unit'], 0, 3))]
            );
            $validated['unit_id'] = $unit->id;
            unset($validated['unit']);
        }

        $companyServiceInventoryUsage->update($validated);

        return response()->json($this->transformItem($companyServiceInventoryUsage->load(['service:id,name,category_id', 'unit', 'inventoryItem'])));
    }

    public function destroy(CompanyServiceInventoryUsage $companyServiceInventoryUsage)
    {
        $companyServiceInventoryUsage->delete();

        return response()->json(null, 204);
    }
}
