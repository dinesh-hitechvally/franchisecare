<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageMediaService
{
    private string $apiKey;
    private string $apiSecret;
    private string $baseUrl;
    private ?string $sourceNumber;

    public function __construct()
    {
        $this->apiKey = config('services.messagemedia.api_key', '');
        $this->apiSecret = config('services.messagemedia.api_secret', '');
        $this->baseUrl = config('services.messagemedia.base_url', 'https://api.messagemedia.com');
        $this->sourceNumber = config('services.messagemedia.source_number');
    }

    /**
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Send an SMS message
     *
     * @param string $to Phone number in E.164 format (e.g., +61412345678)
     * @param string $message The SMS content (max 1600 chars, each 160 chars = 1 SMS part)
     * @param array $options Additional options (source_number, callback_url, etc.)
     * @return array
     */
    public function sendSms(string $to, string $message, array $options = []): array
    {
        if (!$this->isConfigured()) {
            Log::warning('MessageMedia SMS: Service not configured');
            return [
                'success' => false,
                'error' => 'SMS service is not configured',
            ];
        }

        // Format phone number to E.164 if needed
        $formattedTo = $this->formatPhoneNumber($to);
        
        $payload = [
            'messages' => [
                [
                    'content' => $message,
                    'destination_number' => $formattedTo,
                    'format' => 'SMS',
                ],
            ],
        ];

        // Add source number if configured
        if (!empty($options['source_number'])) {
            $payload['messages'][0]['source_number'] = $options['source_number'];
        } elseif ($this->sourceNumber) {
            $payload['messages'][0]['source_number'] = $this->sourceNumber;
        }

        // Add source name for alphanumeric sender ID
        if (!empty($options['source_name'])) {
            $payload['messages'][0]['source_number_type'] = 'ALPHANUMERIC';
            $payload['messages'][0]['source_number'] = substr($options['source_name'], 0, 11);
        }

        // Add callback URL for delivery reports
        if (!empty($options['callback_url'])) {
            $payload['messages'][0]['callback_url'] = $options['callback_url'];
        }

        // Add metadata/reference
        if (!empty($options['metadata'])) {
            $payload['messages'][0]['metadata'] = $options['metadata'];
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->post("{$this->baseUrl}/v1/messages", $payload);

            if ($response->successful()) {
                $body = $response->json();
                $messageData = $body['messages'][0] ?? [];

                Log::info('MessageMedia SMS sent successfully', [
                    'to' => $formattedTo,
                    'message_id' => $messageData['message_id'] ?? null,
                    'status' => $messageData['status'] ?? 'queued',
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageData['message_id'] ?? null,
                    'status' => $messageData['status'] ?? 'queued',
                    'parts' => ceil(strlen($message) / 160),
                    'response' => $body,
                ];
            }

            $errorBody = $response->json();
            Log::error('MessageMedia SMS failed', [
                'to' => $formattedTo,
                'status' => $response->status(),
                'error' => $errorBody,
            ]);

            return [
                'success' => false,
                'error' => $errorBody['message'] ?? $errorBody['details'] ?? 'Failed to send SMS',
                'status_code' => $response->status(),
                'response' => $errorBody,
            ];
        } catch (\Exception $e) {
            Log::error('MessageMedia SMS exception', [
                'to' => $formattedTo,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS to multiple recipients (bulk send)
     *
     * @param array $recipients Array of phone numbers
     * @param string $message The SMS content
     * @param array $options Additional options
     * @return array
     */
    public function sendBulkSms(array $recipients, string $message, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured',
            ];
        }

        $messages = [];
        foreach ($recipients as $to) {
            $msgData = [
                'content' => $message,
                'destination_number' => $this->formatPhoneNumber($to),
                'format' => 'SMS',
            ];

            if (!empty($options['source_number'])) {
                $msgData['source_number'] = $options['source_number'];
            } elseif ($this->sourceNumber) {
                $msgData['source_number'] = $this->sourceNumber;
            }

            if (!empty($options['source_name'])) {
                $msgData['source_number_type'] = 'ALPHANUMERIC';
                $msgData['source_number'] = substr($options['source_name'], 0, 11);
            }

            $messages[] = $msgData;
        }

        $payload = ['messages' => $messages];

        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->timeout(60)
                ->post("{$this->baseUrl}/v1/messages", $payload);

            if ($response->successful()) {
                $body = $response->json();

                Log::info('MessageMedia bulk SMS sent', [
                    'count' => count($recipients),
                    'messages' => count($body['messages'] ?? []),
                ]);

                return [
                    'success' => true,
                    'sent' => count($body['messages'] ?? []),
                    'response' => $body,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Failed to send bulk SMS',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('MessageMedia bulk SMS exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get delivery report status for a message
     *
     * @param string $messageId
     * @return array
     */
    public function getMessageStatus(string $messageId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured',
            ];
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->get("{$this->baseUrl}/v1/messages/{$messageId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get message status',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check account credits/balance
     *
     * @return array
     */
    public function getAccountBalance(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'SMS service is not configured',
            ];
        }

        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->timeout(30)
                ->get("{$this->baseUrl}/v1/reporting/credits");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to get account balance',
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to E.164 format for Australia
     * Handles common Australian formats
     *
     * @param string $phone
     * @return string
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Already in E.164 format
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Australian mobile starting with 04
        if (str_starts_with($phone, '04')) {
            return '+61' . substr($phone, 1);
        }

        // Australian mobile without leading 0 (4xxxxxxxx)
        if (str_starts_with($phone, '4') && strlen($phone) === 9) {
            return '+61' . $phone;
        }

        // Australian landline starting with 0
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '+61' . substr($phone, 1);
        }

        // Already has country code but no +
        if (str_starts_with($phone, '61') && strlen($phone) >= 11) {
            return '+' . $phone;
        }

        // Default: assume Australian, add +61
        return '+61' . ltrim($phone, '0');
    }

    /**
     * Validate phone number format
     *
     * @param string $phone
     * @return bool
     */
    public function isValidPhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);
        
        // Basic E.164 validation: + followed by 10-15 digits
        return (bool) preg_match('/^\+[1-9]\d{9,14}$/', $formatted);
    }

    /**
     * Calculate SMS parts for a message
     *
     * @param string $message
     * @return int
     */
    public function calculateSmsParts(string $message): int
    {
        $length = strlen($message);
        
        // Standard GSM-7 encoding: 160 chars for single SMS, 153 for multipart
        if ($length <= 160) {
            return 1;
        }

        return (int) ceil($length / 153);
    }
}
