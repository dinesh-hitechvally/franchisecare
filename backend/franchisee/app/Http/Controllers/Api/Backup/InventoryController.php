<?php

namespace App\Http\Controllers\Api\Backup;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $query = InventoryItem::query()
            ->with(['unit', 'category'])
            ->where('company_id', $companyId)
            ->orderBy('name');

        // Filter by category slug
        if ($request->filled('category')) {
            $categorySlug = $request->input('category');
            $category = InventoryCategory::where('slug', $categorySlug)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Filter by category_id directly
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        // Filter by booking_usage
        if ($request->filled('booking_usage')) {
            $query->where('booking_usage', filter_var($request->input('booking_usage'), FILTER_VALIDATE_BOOLEAN));
        }

        $items = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'company_id' => $item->company_id,
                'category_id' => $item->category_id,
                'category' => $item->category?->slug,
                'category_name' => $item->category?->name,
                'category_color' => $item->category?->color,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'min_stock' => $item->min_stock,
                'unit_price' => $item->unit_price,
                'unit_id' => $item->unit_id,
                'unit' => $item->unit?->name ?? 'units',
                'notes' => $item->notes,
                'is_active' => $item->is_active,
                'booking_usage' => $item->booking_usage,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:inventory_categories,id',
            'sku' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'booking_usage' => 'boolean',
        ]);

        // Handle unit - find or create
        $unitName = $validated['unit'] ?? 'units';
        $unit = Unit::firstOrCreate(
            ['name' => $unitName],
            ['name' => $unitName]
        );

        // Handle category - by id or slug
        $categoryId = $validated['category_id'] ?? null;
        if (!$categoryId && !empty($validated['category'])) {
            $category = InventoryCategory::firstOrCreate(
                ['slug' => $validated['category']],
                [
                    'name' => ucfirst($validated['category']),
                    'slug' => $validated['category'],
                    'color' => 'bg-slate-100 text-slate-700',
                ]
            );
            $categoryId = $category->id;
        }

        $item = InventoryItem::create([
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?? null,
            'quantity' => $validated['quantity'],
            'min_stock' => $validated['min_stock'] ?? 0,
            'unit_price' => $validated['unit_price'] ?? 0,
            'unit_id' => $unit->id,
            'notes' => $validated['notes'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'booking_usage' => $validated['booking_usage'] ?? false,
        ]);

        $item->load(['unit', 'category']);

        return response()->json([
            'id' => $item->id,
            'company_id' => $item->company_id,
            'category_id' => $item->category_id,
            'category' => $item->category?->slug,
            'category_name' => $item->category?->name,
            'category_color' => $item->category?->color,
            'name' => $item->name,
            'sku' => $item->sku,
            'quantity' => $item->quantity,
            'min_stock' => $item->min_stock,
            'unit_price' => $item->unit_price,
            'unit_id' => $item->unit_id,
            'unit' => $item->unit?->name ?? 'units',
            'notes' => $item->notes,
            'is_active' => $item->is_active,
            'booking_usage' => $item->booking_usage,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ], 201);
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:inventory_categories,id',
            'sku' => 'nullable|string|max:100',
            'quantity' => 'sometimes|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'booking_usage' => 'boolean',
        ]);

        $updateData = [];

        if (isset($validated['name'])) $updateData['name'] = $validated['name'];
        if (array_key_exists('sku', $validated)) $updateData['sku'] = $validated['sku'];
        if (isset($validated['quantity'])) $updateData['quantity'] = $validated['quantity'];
        if (array_key_exists('min_stock', $validated)) $updateData['min_stock'] = $validated['min_stock'] ?? 0;
        if (array_key_exists('unit_price', $validated)) $updateData['unit_price'] = $validated['unit_price'] ?? 0;
        if (array_key_exists('notes', $validated)) $updateData['notes'] = $validated['notes'];
        if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];
        if (isset($validated['booking_usage'])) $updateData['booking_usage'] = $validated['booking_usage'];

        // Handle category - by id or slug
        if (isset($validated['category_id'])) {
            $updateData['category_id'] = $validated['category_id'];
        } elseif (!empty($validated['category'])) {
            $category = InventoryCategory::firstOrCreate(
                ['slug' => $validated['category']],
                [
                    'name' => ucfirst($validated['category']),
                    'slug' => $validated['category'],
                    'color' => 'bg-slate-100 text-slate-700',
                ]
            );
            $updateData['category_id'] = $category->id;
        }

        // Handle unit
        if (isset($validated['unit'])) {
            $unit = Unit::firstOrCreate(
                ['name' => $validated['unit']],
                ['name' => $validated['unit']]
            );
            $updateData['unit_id'] = $unit->id;
        }

        $inventoryItem->update($updateData);
        $inventoryItem->load(['unit', 'category']);

        return response()->json([
            'id' => $inventoryItem->id,
            'company_id' => $inventoryItem->company_id,
            'category_id' => $inventoryItem->category_id,
            'category' => $inventoryItem->category?->slug,
            'category_name' => $inventoryItem->category?->name,
            'category_color' => $inventoryItem->category?->color,
            'name' => $inventoryItem->name,
            'sku' => $inventoryItem->sku,
            'quantity' => $inventoryItem->quantity,
            'min_stock' => $inventoryItem->min_stock,
            'unit_price' => $inventoryItem->unit_price,
            'unit_id' => $inventoryItem->unit_id,
            'unit' => $inventoryItem->unit?->name ?? 'units',
            'notes' => $inventoryItem->notes,
            'is_active' => $inventoryItem->is_active,
            'booking_usage' => $inventoryItem->booking_usage,
            'created_at' => $inventoryItem->created_at,
            'updated_at' => $inventoryItem->updated_at,
        ]);
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();

        return response()->json(null, 204);
    }
}