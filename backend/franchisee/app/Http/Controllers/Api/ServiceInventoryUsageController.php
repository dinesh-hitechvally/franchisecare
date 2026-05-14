<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceInventoryUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ServiceInventoryUsageController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceInventoryUsage::with('service:id,name,category_id')
            ->orderBy('inventory_name')
            ->orderByDesc('id');

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('inventory_name', 'like', $term)
                    ->orWhere('unit', 'like', $term)
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

        $usage = ServiceInventoryUsage::create($validated);

        return response()->json($usage->load('service:id,name,category_id'), 201);
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

        $serviceInventoryUsage->update($validated);

        return response()->json($serviceInventoryUsage->load('service:id,name,category_id'));
    }

    public function destroy(ServiceInventoryUsage $serviceInventoryUsage)
    {
        $serviceInventoryUsage->delete();

        return response()->json(null, 204);
    }
}