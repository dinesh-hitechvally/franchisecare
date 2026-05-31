<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\LeadServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOLID LeadController
 * 
 * Single Responsibility: Only handles HTTP request/response concerns
 * Open/Closed: Extensible through service injection
 * Dependency Inversion: Depends on LeadServiceInterface abstraction
 */
class LeadController extends Controller
{
    public function __construct(
        private LeadServiceInterface $leadService
    ) {}

    /**
     * Display a listing of leads.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];

        $leads = $this->leadService->listLeads($filters);

        return response()->json($leads);
    }

    /**
     * Store a newly created lead.
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->createLead($request->leadData());

        return response()->json($lead, 201);
    }

    /**
     * Display the specified lead.
     */
    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead);
    }

    /**
     * Update the specified lead.
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $lead = $this->leadService->updateLead($lead, $request->leadData());

        return response()->json($lead);
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $this->leadService->deleteLead($lead);

        return response()->json(null, 204);
    }

    /**
     * Convert lead to customer.
     */
    public function convert(Lead $lead): JsonResponse
    {
        $lead = $this->leadService->convertLead($lead);

        return response()->json([
            'message' => 'Lead converted successfully',
            'lead' => $lead,
        ]);
    }
}
