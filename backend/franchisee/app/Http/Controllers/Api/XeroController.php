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
        ]);

        $result = $this->xeroIntegrationService->callback(
            $request->user(),
            $request->code,
            $request->state
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

    public function test(Request $request): JsonResponse
    {
        return response()->json($this->xeroIntegrationService->test($request->user()));
    }
}
