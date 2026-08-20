<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Contracts\Services\XeroIntegrationServiceInterface;
use Illuminate\Http\JsonResponse;

class XeroController extends Controller
{
    public function __construct(
        protected XeroIntegrationServiceInterface $xeroIntegrationService
    ) {}

    /**
     * Unauthenticated cron endpoint for cPanel/external schedulers.
     */
    public function pushPendingBookings(): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->pushPendingBookingsForAllCompanies());
    }

    public function pushPendingCustomers(): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->pushPendingCustomersForAllCompanies());
    }

    public function pushPendingPurchases(): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->pushPendingPurchasesForAllCompanies());
    }

    public function pushPendingInventoryItems(): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->pushPendingInventoryItemsForAllCompanies());
    }

    public function pushPendingServices(): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->pushPendingServicesForAllCompanies());
    }
}
