<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query()->latest();

        if ($request->user()?->company_id) {
            $query->where('company_id', $request->user()->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('address', 'like', $search);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:255',
            'alternate_phone' => 'nullable|string|max:255',
            'interested_services' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:50',
            'pet_breed' => 'nullable|string|max:255',
            'referred_by' => 'nullable|string|max:255',
            'additional_note' => 'nullable|string',
            'notes' => 'nullable|string',
            'source' => 'nullable|in:phone,internet,walk-in,referral',
            'leads_from' => 'nullable|in:phone,internet',
            'status' => 'nullable|in:new,contacted,qualified,converted,lost,snoozed,completed,cancellation_request,message_for_operator',
            'snoozed_until' => 'nullable|date',
        ]);

        $validated['company_id'] = $request->user()?->company_id;
        $validated['customer_name'] = $validated['customer_name'] ?? trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

        $lead = Lead::create($validated);

        return response()->json($lead, 201);
    }

    public function show(Lead $lead)
    {
        return response()->json($lead);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'alternate_phone' => 'nullable|string|max:255',
            'interested_services' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'suburb' => 'nullable|string|max:255',
            'postcode' => 'nullable|string|max:50',
            'pet_breed' => 'nullable|string|max:255',
            'referred_by' => 'nullable|string|max:255',
            'additional_note' => 'nullable|string',
            'notes' => 'nullable|string',
            'source' => 'nullable|in:phone,internet,walk-in,referral',
            'leads_from' => 'nullable|in:phone,internet',
            'status' => 'nullable|in:new,contacted,qualified,converted,lost,snoozed,completed,cancellation_request,message_for_operator',
            'snoozed_until' => 'nullable|date',
        ]);

        if (!isset($validated['customer_name']) && (isset($validated['first_name']) || isset($validated['last_name']))) {
            $firstName = $validated['first_name'] ?? $lead->first_name;
            $lastName = $validated['last_name'] ?? $lead->last_name;
            $validated['customer_name'] = trim($firstName . ' ' . $lastName);
        }

        $lead->update($validated);

        return response()->json($lead->fresh());
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return response()->json(null, 204);
    }

    public function convert(Request $request, Lead $lead)
    {
        $lead->update([
            'status' => 'converted',
        ]);

        return response()->json([
            'message' => 'Lead converted successfully',
            'lead' => $lead->fresh(),
        ]);
    }
}
