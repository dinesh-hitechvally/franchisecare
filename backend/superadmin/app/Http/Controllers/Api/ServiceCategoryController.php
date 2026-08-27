<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceCategory::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
        ]);

        $validated['status'] = 'ACTIVE';
        $validated['sort_order'] = ServiceCategory::max('sort_order') + 1;

        $category = ServiceCategory::create($validated);

        return response()->json($category, 201);
    }

    public function show(ServiceCategory $serviceCategory)
    {
        return response()->json($serviceCategory);
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $serviceCategory->update($validated);

        return response()->json($serviceCategory);
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();

        return response()->json(['message' => 'Service category deleted successfully']);
    }
}
