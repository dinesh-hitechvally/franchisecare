<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $query = InventoryItem::query()
            ->where('company_id', $companyId)
            ->orderBy('name');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'sku' => 'required|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        $validated['unit_price'] = $validated['unit_price'] ?? 0;
        $validated['unit'] = $validated['unit'] ?? 'units';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = InventoryItem::create($validated);

        return response()->json($item, 201);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
            'sku' => 'sometimes|string|max:100',
            'quantity' => 'sometimes|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $inventoryItem->update($validated);

        return response()->json($inventoryItem);
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        return response()->json(null, 204);
    }
}