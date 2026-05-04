<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerItem;
use App\Models\Customer;
use App\Models\CustomerItemAudit;
use App\Http\Resources\PetResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PetResource::collection(CustomerItem::latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date',
            'breed' => 'nullable|string|max:255',
            'size' => 'required|string|max:50',
            'image' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
            'customer_id' => 'required|exists:customers,id',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('customer_items', 'public');
            $validated['image'] = $path;
        }

        $pet = CustomerItem::create($validated);

        // Record Audit
        CustomerItemAudit::create(array_merge($pet->toArray(), [
            'item_id' => $pet->id,
            'action_type' => 'created'
        ]));

        return new PetResource($pet);
    }

    /**
     * Display the specified resource.
     */
    public function show(CustomerItem $pet)
    {
        return new PetResource($pet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerItem $pet)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'gender' => 'sometimes|nullable|in:male,female',
            'birth_date' => 'sometimes|nullable|date',
            'breed' => 'sometimes|nullable|string|max:255',
            'size' => 'sometimes|string|max:50',
            'image' => 'sometimes|nullable|image|max:5120',
            'notes' => 'sometimes|nullable|string',
            'customer_id' => 'sometimes|exists:customers,id',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($pet->image) {
                Storage::disk('public')->delete($pet->image);
            }
            $path = $request->file('image')->store('customer_items', 'public');
            $validated['image'] = $path;
        } elseif ($request->boolean('remove_image')) {
            if ($pet->image) {
                Storage::disk('public')->delete($pet->image);
            }
            $validated['image'] = null;
        }

        $pet->update($validated);

        // Record Audit
        CustomerItemAudit::create(array_merge($pet->fresh()->toArray(), [
            'item_id' => $pet->id,
            'action_type' => 'updated'
        ]));

        return new PetResource($pet);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerItem $pet)
    {
        $pet->delete();
        return response()->json(null, 204);
    }

    /**
     * Display pets for a specific customer.
     */
    public function getByCustomer(Customer $customer)
    {
        return PetResource::collection($customer->customerItems()->latest()->get());
    }

    /**
     * Display the audit history for the specified pet.
     */
    public function getHistory(CustomerItem $pet)
    {
        $history = CustomerItemAudit::where('item_id', $pet->id)
            ->latest()
            ->paginate(5);

        return response()->json($history);
    }
}
