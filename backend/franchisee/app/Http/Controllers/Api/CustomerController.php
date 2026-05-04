<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAudit;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Filter by authenticated user's company_id
        if (auth()->check() && auth()->user()->company_id) {
            $query->where('company_id', auth()->user()->company_id);
        }

        // Filter by archive status
        if ($request->get('status') === 'archived') {
            $query->where('is_archived', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false)->where('is_archived', false);
        } else {
            $query->where('is_active', true)->where('is_archived', false);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return CustomerResource::collection($query->with('customerItems')->latest()->get())->resolve();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers',
            'phone' => 'required|string',
            'other_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'street_address' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'other_email' => 'nullable|email',
            'referred_by' => 'nullable|string',
            'is_ndis' => 'nullable|boolean',
            'is_subscribed' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        // Set company_id from authenticated user
        $validated['company_id'] = auth()->user()->company_id;

        $customer = Customer::create($validated);

        // Record Audit
        CustomerAudit::create(array_merge($customer->toArray(), [
            'customer_id' => $customer->id,
            'action_type' => 'created'
        ]));

        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $customer = Customer::with('customerItems')->findOrFail($id);
        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:customers,email,' . $id,
            'other_email' => 'nullable|email',
            'phone' => 'sometimes|required|string',
            'other_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'street_address' => 'nullable|string|max:255',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'referred_by' => 'nullable|string',
            'is_ndis' => 'nullable|boolean',
            'is_subscribed' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $customer->update($validated);

        // Record Audit
        CustomerAudit::create(array_merge($customer->fresh()->toArray(), [
            'customer_id' => $customer->id,
            'action_type' => 'updated'
        ]));

        return (new CustomerResource($customer->fresh(['customerItems'])))->resolve();
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_archived' => true]);

        // Record Audit
        CustomerAudit::create(array_merge($customer->fresh()->toArray(), [
            'customer_id' => $customer->id,
            'action_type' => 'archived'
        ]));

        return response()->json(['message' => 'Customer archived successfully']);
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        $customer = Customer::where('is_archived', true)->findOrFail($id);
        $customer->update(['is_archived' => false]);

        // Record Audit
        CustomerAudit::create(array_merge($customer->fresh()->toArray(), [
            'customer_id' => $customer->id,
            'action_type' => 'restored'
        ]));

        return response()->json(['message' => 'Customer restored successfully']);
    }

    /**
     * Display the audit history for the specified customer.
     */
    public function getHistory($id)
    {
        $customer = Customer::findOrFail($id);
        $history = CustomerAudit::where('customer_id', $customer->id)
            ->latest()
            ->paginate(5);

        return response()->json($history);
    }
}
