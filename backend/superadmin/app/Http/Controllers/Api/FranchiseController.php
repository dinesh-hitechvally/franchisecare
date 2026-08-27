<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Franchise;
use App\Models\FranchiseAudit;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function index(Request $request)
    {
        $query = Franchise::query();

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('owner_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($state = $request->get('state')) {
            $query->where('state', $state);
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:franchises',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:franchises',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'postcode' => 'nullable|string|max:10',
            'abn' => 'nullable|string|max:20',
            'franchisee_type' => 'nullable|in:master_franchisee,franchisee,franchisor',
            'has_ipad' => 'nullable|boolean',
            'franchise_fee' => 'nullable|numeric|min:0',
            'royalty_percentage' => 'nullable|numeric|min:0|max:100',
            'marketing_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'contract_length' => 'nullable|integer|min:1',
            'territory' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';
        $franchise = Franchise::create($validated);

        $this->createAudit($franchise, 'created', $validated);

        return response()->json($franchise, 201);
    }

    public function show(Franchise $franchise)
    {
        $franchise->load(['users', 'services', 'suburbs']);
        
        return response()->json($franchise);
    }

    public function update(Request $request, Franchise $franchise)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:franchises,code,' . $franchise->id,
            'owner_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:franchises,email,' . $franchise->id,
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'postcode' => 'nullable|string|max:10',
            'abn' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,suspended,terminated',
            'franchisee_type' => 'nullable|in:master_franchisee,franchisee,franchisor',
            'has_ipad' => 'nullable|boolean',
            'tscs_accepted' => 'nullable|boolean',
            'franchise_fee' => 'nullable|numeric|min:0',
            'royalty_percentage' => 'nullable|numeric|min:0|max:100',
            'marketing_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'contract_length' => 'nullable|integer|min:1',
            'territory' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (array_key_exists('tscs_accepted', $validated) && $validated['tscs_accepted'] && ! $franchise->tscs_accepted) {
            $validated['tscs_accepted_at'] = now();
        }

        $oldData = $franchise->toArray();
        $franchise->update($validated);

        $this->createAudit($franchise, 'updated', [
            'old' => $oldData,
            'new' => $franchise->fresh()->toArray(),
        ]);

        return response()->json($franchise);
    }

    public function destroy(Franchise $franchise)
    {
        $this->createAudit($franchise, 'deleted', $franchise->toArray());
        
        $franchise->delete();

        return response()->json(['message' => 'Franchise deleted successfully']);
    }

    public function getHistory(Franchise $franchise)
    {
        $audits = $franchise->audits()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($audits);
    }

    public function updateStatus(Request $request, Franchise $franchise)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended,terminated',
            'reason' => 'nullable|string',
        ]);

        $oldStatus = $franchise->status;
        $franchise->update(['status' => $request->status]);

        $this->createAudit($franchise, 'status_changed', [
            'from' => $oldStatus,
            'to' => $request->status,
            'reason' => $request->reason,
        ]);

        return response()->json($franchise);
    }

    protected function createAudit(Franchise $franchise, string $action, array $changes)
    {
        FranchiseAudit::create([
            'franchise_id' => $franchise->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'changes' => $changes,
            'ip_address' => request()->ip(),
        ]);
    }
}
