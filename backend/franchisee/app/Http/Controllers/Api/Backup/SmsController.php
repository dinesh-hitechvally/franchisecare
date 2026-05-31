<?php

namespace App\Http\Controllers\Api\Backup;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsHistory;
use App\Services\MessageMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    public function __construct(
        private MessageMediaService $smsService
    ) {}

    /**
     * Send SMS to a customer
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_number' => 'required|string|max:30',
            'message' => 'required|string|max:1600',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_name' => 'nullable|string|max:255',
        ]);

        // Check if service is configured
        if (! $this->smsService->isConfigured()) {
            return response()->json([
                'message' => 'SMS service is not configured. Please add MessageMedia API credentials.',
            ], 503);
        }

        // Validate phone number
        if (! $this->smsService->isValidPhoneNumber($validated['to_number'])) {
            throw ValidationException::withMessages([
                'to_number' => 'Invalid phone number format.',
            ]);
        }

        // Get customer name if customer_id provided
        $customerName = $validated['customer_name'] ?? null;
        if (! $customerName && ! empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $customerName = $customer ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) : null;
        }

        // Send SMS
        $result = $this->smsService->sendSms(
            $validated['to_number'],
            $validated['message'],
            [
                'source_name' => config('services.messagemedia.source_name'),
            ]
        );

        // Record in history
        $record = SmsHistory::create([
            'company_id' => $request->user()?->company_id,
            'to_number' => $this->smsService->formatPhoneNumber($validated['to_number']),
            'customer_name' => $customerName,
            'message' => $validated['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'gateway_response' => json_encode([
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                'parts' => $result['parts'] ?? 1,
            ]),
            'sent_at' => $result['success'] ? now() : null,
        ]);

        if (! $result['success']) {
            return response()->json([
                'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                'data' => $record,
            ], 500);
        }

        return response()->json([
            'message' => 'SMS sent successfully.',
            'data' => $record,
        ], 201);
    }

    /**
     * Send bulk SMS to multiple customers
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'message' => 'required|string|max:1600',
        ]);

        if (! $this->smsService->isConfigured()) {
            return response()->json([
                'message' => 'SMS service is not configured.',
            ], 503);
        }

        $customers = Customer::whereIn('id', $validated['customer_ids'])
            ->whereNotNull('phone')
            ->get();

        if ($customers->isEmpty()) {
            return response()->json([
                'message' => 'No customers with valid phone numbers found.',
            ], 400);
        }

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'records' => [],
        ];

        foreach ($customers as $customer) {
            if (! $this->smsService->isValidPhoneNumber($customer->phone)) {
                $results['skipped']++;
                continue;
            }

            $result = $this->smsService->sendSms(
                $customer->phone,
                $validated['message'],
                [
                    'source_name' => config('services.messagemedia.source_name'),
                    'metadata' => [
                        'customer_id' => $customer->id,
                    ],
                ]
            );

            $record = SmsHistory::create([
                'company_id' => $request->user()?->company_id,
                'to_number' => $this->smsService->formatPhoneNumber($customer->phone),
                'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'message' => $validated['message'],
                'status' => $result['success'] ? 'sent' : 'failed',
                'gateway_response' => json_encode([
                    'message_id' => $result['message_id'] ?? null,
                    'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                ]),
                'sent_at' => $result['success'] ? now() : null,
            ]);

            $results['records'][] = $record;

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
        }

        return response()->json([
            'message' => "Bulk SMS: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped",
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped'],
        ]);
    }

    /**
     * Send SMS to a specific customer
     */
    public function sendToCustomer(Customer $customer, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1600',
        ]);

        if (! $customer->phone) {
            throw ValidationException::withMessages([
                'phone' => 'Customer does not have a phone number.',
            ]);
        }

        if (! $this->smsService->isConfigured()) {
            return response()->json([
                'message' => 'SMS service is not configured.',
            ], 503);
        }

        if (! $this->smsService->isValidPhoneNumber($customer->phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Customer has an invalid phone number format.',
            ]);
        }

        $result = $this->smsService->sendSms(
            $customer->phone,
            $validated['message'],
            [
                'source_name' => config('services.messagemedia.source_name'),
                'metadata' => [
                    'customer_id' => $customer->id,
                ],
            ]
        );

        $record = SmsHistory::create([
            'company_id' => $request->user()?->company_id,
            'to_number' => $this->smsService->formatPhoneNumber($customer->phone),
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'message' => $validated['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'gateway_response' => json_encode([
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                'parts' => $result['parts'] ?? 1,
            ]),
            'sent_at' => $result['success'] ? now() : null,
        ]);

        if (! $result['success']) {
            return response()->json([
                'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                'data' => $record,
            ], 500);
        }

        return response()->json([
            'message' => 'SMS sent successfully.',
            'data' => $record,
        ], 201);
    }

    /**
     * Get SMS service status and balance
     */
    public function status(): JsonResponse
    {
        if (! $this->smsService->isConfigured()) {
            return response()->json([
                'configured' => false,
                'message' => 'SMS service is not configured.',
            ]);
        }

        $balance = $this->smsService->getAccountBalance();

        return response()->json([
            'configured' => true,
            'balance' => $balance['success'] ? $balance['data'] : null,
            'error' => $balance['success'] ? null : $balance['error'],
        ]);
    }

    /**
     * Get message delivery status
     */
    public function messageStatus(string $messageId): JsonResponse
    {
        if (! $this->smsService->isConfigured()) {
            return response()->json([
                'message' => 'SMS service is not configured.',
            ], 503);
        }

        $result = $this->smsService->getMessageStatus($messageId);

        if (! $result['success']) {
            return response()->json([
                'message' => 'Failed to get message status: ' . ($result['error'] ?? 'Unknown error'),
            ], 500);
        }

        return response()->json($result['data']);
    }

    /**
     * Calculate SMS parts for a message (useful for preview)
     */
    public function calculateParts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        return response()->json([
            'parts' => $this->smsService->calculateSmsParts($validated['message']),
            'characters' => strlen($validated['message']),
        ]);
    }
}
