<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FranchiseSuburb;
use Illuminate\Http\Request;

class FranchiseSuburbController extends Controller
{
    public function index(Request $request)
    {
        $query = FranchiseSuburb::query()->with('franchise:id,name,code');

        if ($search = $request->get('search')) {
            $query->where('suburb_name', 'like', "%{$search}%");
        }

        if ($franchiseId = $request->get('franchise_id')) {
            $query->where('franchise_id', $franchiseId);
        }

        if ($state = $request->get('state')) {
            $query->where('state', $state);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->get('per_page', 15);

        return response()->json($query->orderBy('suburb_name')->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'franchise_id' => 'required|exists:franchises,id',
            'suburb_name' => 'required|string|max:100',
            'postcode' => 'required|string|max:10',
            'state' => 'required|string|max:50',
        ]);

        $validated['status'] = 'ACTIVE';

        $suburb = FranchiseSuburb::create($validated);

        return response()->json($suburb->load('franchise:id,name,code'), 201);
    }

    public function show(FranchiseSuburb $franchiseSuburb)
    {
        return response()->json($franchiseSuburb->load('franchise:id,name,code'));
    }

    public function update(Request $request, FranchiseSuburb $franchiseSuburb)
    {
        $validated = $request->validate([
            'franchise_id' => 'sometimes|exists:franchises,id',
            'suburb_name' => 'sometimes|string|max:100',
            'postcode' => 'sometimes|string|max:10',
            'state' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:ACTIVE,INACTIVE',
        ]);

        $franchiseSuburb->update($validated);

        return response()->json($franchiseSuburb->load('franchise:id,name,code'));
    }

    public function destroy(FranchiseSuburb $franchiseSuburb)
    {
        $franchiseSuburb->delete();

        return response()->json(['message' => 'Franchise suburb deleted successfully']);
    }
}
