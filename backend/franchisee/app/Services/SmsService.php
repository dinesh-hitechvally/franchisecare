<?php

namespace App\Services;

use App\Contracts\Services\SmsServiceInterface;
use App\Models\Customer;
use App\Models\SmsHistory;
use App\Services\MessageMediaService;
use Illuminate\Contracts\Auth\Authenticatable;

class SmsService implements SmsServiceInterface
{
    public function __construct(
        private MessageMediaService $messageMediaService
    ) {}

    public function send(Authenticatable $user, array $data): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured. Please add MessageMedia API credentials.',
                'status_code' => 503,
            ];
        }

        if (!$this->messageMediaService->isValidPhoneNumber($data['to_number'])) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format.',
                'validation_error' => 'to_number',
            ];
        }

        $customerName = $data['customer_name'] ?? null;
        if (!$customerName && !empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
            $customerName = $customer ? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) : null;
        }

        $result = $this->messageMediaService->sendSms(
            $data['to_number'],
            $data['message'],
            [
                'source_name' => config('services.messagemedia.source_name'),
            ]
        );

        $record = SmsHistory::create([
            'company_id' => $user->company_id ?? null,
            'to_number' => $this->messageMediaService->formatPhoneNumber($data['to_number']),
            'customer_name' => $customerName,
            'message' => $data['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'gateway_response' => json_encode([
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                'parts' => $result['parts'] ?? 1,
            ]),
            'sent_at' => $result['success'] ? now() : null,
        ]);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                'data' => $record,
                'status_code' => 500,
            ];
        }

        return [
            'success' => true,
            'message' => 'SMS sent successfully.',
            'data' => $record,
        ];
    }

    public function sendBulk(Authenticatable $user, array $data): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured.',
                'status_code' => 503,
            ];
        }

        $customers = Customer::whereIn('id', $data['customer_ids'])
            ->whereNotNull('phone')
            ->get();

        if ($customers->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No customers with valid phone numbers found.',
                'status_code' => 400,
            ];
        }

        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'records' => [],
        ];

        foreach ($customers as $customer) {
            if (!$this->messageMediaService->isValidPhoneNumber($customer->phone)) {
                $results['skipped']++;
                continue;
            }

            $result = $this->messageMediaService->sendSms(
                $customer->phone,
                $data['message'],
                [
                    'source_name' => config('services.messagemedia.source_name'),
                    'metadata' => [
                        'customer_id' => $customer->id,
                    ],
                ]
            );

            $record = SmsHistory::create([
                'company_id' => $user->company_id ?? null,
                'to_number' => $this->messageMediaService->formatPhoneNumber($customer->phone),
                'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'message' => $data['message'],
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

        return [
            'success' => true,
            'message' => "Bulk SMS: {$results['sent']} sent, {$results['failed']} failed, {$results['skipped']} skipped",
            'sent' => $results['sent'],
            'failed' => $results['failed'],
            'skipped' => $results['skipped'],
        ];
    }

    public function sendToCustomer(Authenticatable $user, Customer $customer, string $message): array
    {
        if (!$customer->phone) {
            return [
                'success' => false,
                'error' => 'Customer does not have a phone number.',
                'validation_error' => 'phone',
            ];
        }

        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured.',
                'status_code' => 503,
            ];
        }

        if (!$this->messageMediaService->isValidPhoneNumber($customer->phone)) {
            return [
                'success' => false,
                'error' => 'Customer has an invalid phone number format.',
                'validation_error' => 'phone',
            ];
        }

        $result = $this->messageMediaService->sendSms(
            $customer->phone,
            $message,
            [
                'source_name' => config('services.messagemedia.source_name'),
                'metadata' => [
                    'customer_id' => $customer->id,
                ],
            ]
        );

        $record = SmsHistory::create([
            'company_id' => $user->company_id ?? null,
            'to_number' => $this->messageMediaService->formatPhoneNumber($customer->phone),
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'gateway_response' => json_encode([
                'message_id' => $result['message_id'] ?? null,
                'status' => $result['status'] ?? ($result['error'] ?? 'unknown'),
                'parts' => $result['parts'] ?? 1,
            ]),
            'sent_at' => $result['success'] ? now() : null,
        ]);

        if (!$result['success']) {
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'),
                'data' => $record,
                'status_code' => 500,
            ];
        }

        return [
            'success' => true,
            'message' => 'SMS sent successfully.',
            'data' => $record,
        ];
    }

    public function getStatus(): array
    {
        if (!$this->isConfigured()) {
            return [
                'configured' => false,
                'message' => 'SMS service is not configured.',
            ];
        }

        $balance = $this->messageMediaService->getAccountBalance();

        return [
            'configured' => true,
            'balance' => $balance['success'] ? $balance['data'] : null,
            'error' => $balance['success'] ? null : $balance['error'],
        ];
    }

    public function getMessageStatus(string $messageId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured.',
                'status_code' => 503,
            ];
        }

        $result = $this->messageMediaService->getMessageStatus($messageId);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => 'Failed to get message status: ' . ($result['error'] ?? 'Unknown error'),
                'status_code' => 500,
            ];
        }

        return [
            'success' => true,
            'data' => $result['data'],
        ];
    }

    public function calculateParts(string $message): array
    {
        return [
            'parts' => $this->messageMediaService->calculateSmsParts($message),
            'characters' => strlen($message),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->messageMediaService->isConfigured();
    }
}
