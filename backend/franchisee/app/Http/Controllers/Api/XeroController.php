<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\XeroIntegrationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XeroController extends Controller
{
    public function __construct(
        protected XeroIntegrationServiceInterface $xeroIntegrationService
    ) {}

    public function status(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->status($request->user()));
    }

    public function getAuthUrl(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->authorize($request->user()));
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
            'scope' => 'nullable|string',
        ]);

        $result = $this->xeroIntegrationService->callback(
            $request->user(),
            $request->code,
            $request->state,
            $request->scope
        );

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 400);
        }

        return response()->json($result);
    }

    public function disconnect(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->disconnect($request->user()));
    }

    public function accounts(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->accounts($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json(['accounts' => $result['accounts']]);
    }

    public function taxRates(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->taxRates($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json(['tax_rates' => $result['tax_rates']]);
    }

    public function getSettings(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->getSettings($request->user()));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_supplier_name' => 'nullable|string|max:255',
            'bank_account_code' => 'nullable|string|max:50',
            'inventory_asset_account_code' => 'nullable|string|max:50',
            'inventory_cogs_account_code' => 'nullable|string|max:50',
            'inventory_sales_account_code' => 'nullable|string|max:50',
            'service_sales_account_code' => 'nullable|string|max:50',
            'default_tax_type' => 'nullable|string|max:50',
        ]);

        return response()->json($this->xeroIntegrationService->updateSettings($request->user(), $validated));
    }

    public function syncBooking(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $result = $this->xeroIntegrationService->syncBooking($request->user(), $request->booking_id);

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function pushPendingBookings(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->pushPendingBookings($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function syncCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $result = $this->xeroIntegrationService->syncCustomer($request->user(), $request->customer_id);

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function pushPendingCustomers(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->pushPendingCustomers($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function syncPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|exists:inventory_orders,id',
        ]);

        $result = $this->xeroIntegrationService->syncPurchase($request->user(), $request->order_id);

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function pushPendingPurchases(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->pushPendingPurchases($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function syncInventoryItem(Request $request): JsonResponse
    {
        $request->validate([
            'inventory_item_id' => 'required|exists:inventory_items,id',
        ]);

        $result = $this->xeroIntegrationService->syncInventoryItem($request->user(), $request->inventory_item_id);

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function pushPendingInventoryItems(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->pushPendingInventoryItems($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function syncService(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
        ]);

        $result = $this->xeroIntegrationService->syncService($request->user(), $request->service_id);

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function pushPendingServices(Request $request): JsonResponse
    {
        $result = $this->xeroIntegrationService->pushPendingServices($request->user());

        if (!($result['success'] ?? true)) {
            return response()->json(['error' => $result['error']], $result['status_code'] ?? 500);
        }

        return response()->json($result);
    }

    public function test(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->test($request->user()));
    }
}
