<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InventoryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $query = InventoryCategory::query()
            ->where(function ($q) use ($companyId) {
                // Global categories (company_id is null) OR company-specific
                $q->whereNull('company_id')
                  ->orWhere('company_id', $companyId);
            })
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $companyId = Auth::user()?->company_id ?? Auth::user()?->franchise_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        $category = InventoryCategory::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'slug' => $slug,
            'color' => $validated['color'] ?? 'bg-slate-100 text-slate-700',
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, InventoryCategory $inventoryCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $inventoryCategory->update($validated);

        return response()->json($inventoryCategory);
    }

    public function destroy(InventoryCategory $inventoryCategory)
    {
        // Check if category has items
        if ($inventoryCategory->items()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing items'
            ], 422);
        }

        $inventoryCategory->delete();

        return response()->json(null, 204);
    }
}
