<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FranchiseService;
use Illuminate\Http\Request;

class FranchiseServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = FranchiseService::query()->with(['franchise:id,name,code', 'service:id,name']);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($franchiseId = $request->get('franchise_id')) {
            $query->where('franchise_id', $franchiseId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->get('per_page', 15);

        return response()->json($query->orderBy('name')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'service_id' => 'nullable|exists:services,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        $validated['status'] = 'ACTIVE';

        $franchiseService = FranchiseService::create($validated);

        return response()->json($franchiseService->load(['franchise:id,name,code', 'service:id,name']), 201);
    }

    public function show(FranchiseService $franchiseService)
    {
        return response()->json($franchiseService->load(['franchise:id,name,code', 'service:id,name']));
    }

    public function update(Request $request, FranchiseService $franchiseService)
    {
        $validated = $request->validate([
            'franchise_id' => 'sometimes|exists:franchises,id',
            'service_id' => 'nullable|exists:services,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'duration' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $franchiseService->update($validated);

        return response()->json($franchiseService->load(['franchise:id,name,code', 'service:id,name']));
    }

    public function destroy(FranchiseService $franchiseService)
    {
        $franchiseService->delete();

        return response()->json(['message' => 'Franchise service deleted successfully']);
    }
}
