<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\SmsServiceInterface;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    public function __construct(
        private SmsServiceInterface $smsService
    ) {}

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_number' => 'required|string|max:30',
            'message' => 'required|string|max:1600',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $result = $this->smsService->send($request->user(), $validated);

        if (!empty($result['validation_error'])) {
            throw ValidationException::withMessages([
                $result['validation_error'] => $result['error'],
            ]);
        }

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'] ?? $result['message'],
                'data' => $result['data'] ?? null,
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], 201);
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'message' => 'required|string|max:1600',
        ]);

        $result = $this->smsService->sendBulk($request->user(), $validated);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'message' => $result['message'],
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'skipped' => $result['skipped'],
        ]);
    }

    public function sendToCustomer(Customer $customer, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1600',
        ]);

        $result = $this->smsService->sendToCustomer($request->user(), $customer, $validated['message']);

        if (!empty($result['validation_error'])) {
            throw ValidationException::withMessages([
                $result['validation_error'] => $result['error'],
            ]);
        }

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'] ?? $result['error'],
                'data' => $result['data'] ?? null,
            ], $result['status_code'] ?? 500);
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['data'],
        ], 201);
    }

    public function status(): JsonResponse
    {
        return response()->json($this->smsService->getStatus());
    }

    public function messageStatus(string $messageId): JsonResponse
    {
        $result = $this->smsService->getMessageStatus($messageId);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['error'],
            ], $result['status_code'] ?? 500);
        }

        return response()->json($result['data']);
    }

    public function calculateParts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        return response()->json($this->smsService->calculateParts($validated['message']));
    }
}
