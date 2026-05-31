<?php

namespace App\Http\Controllers\Api\Backup;

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
        $this->recordCustomerAudit($customer, 'created');

        return (new CustomerResource($customer))->resolve();
    }

    /**
     * Record a customer audit entry
     */
    private function recordCustomerAudit(Customer $customer, string $actionType): void
    {
        $auditData = $customer->only([
            'first_name', 'last_name', 'email', 'other_email',
            'phone', 'other_phone', 'address', 'street_address',
            'suburb', 'postcode', 'state', 'company_id', 'notes',
            'referred_by', 'is_ndis', 'is_subscribed', 'is_active',
            'latitude', 'longitude', 'reference_id', 'is_archived'
        ]);
        
        $auditData['customer_id'] = $customer->id;
        $auditData['action_type'] = $actionType;
        $auditData['action_at'] = now();
        
        CustomerAudit::create($auditData);
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
        $this->recordCustomerAudit($customer->fresh(), 'updated');

        return (new CustomerResource($customer->fresh(['customerItems'])))->resolve();
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['is_archived' => true]);

        // Record Audit
        $this->recordCustomerAudit($customer->fresh(), 'archived');

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
        $this->recordCustomerAudit($customer->fresh(), 'restored');

        return response()->json(['message' => 'Customer restored successfully']);
    }

    /**
     * Display the audit history for the specified customer.
     */
    public function getHistory($id)
    {
        $customer = Customer::findOrFail($id);
        $history = CustomerAudit::where('customer_id', $customer->id)
            ->orderByDesc('action_at')
            ->orderByDesc('id')
            ->paginate(5);

        return response()->json($history);
    }
}
