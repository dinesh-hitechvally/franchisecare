<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CyberSourceService
{
    private string $merchantId;
    private string $apiKeyId;
    private string $secretKey;
    private string $baseUrl;
    private bool $sandbox;

    public function __construct()
    {
        $this->merchantId = config('services.cybersource.merchant_id', '');
        $this->apiKeyId = config('services.cybersource.api_key_id', '');
        $this->secretKey = config('services.cybersource.secret_key', '');
        $this->sandbox = config('services.cybersource.sandbox', true);
        $this->baseUrl = $this->sandbox 
            ? 'https://apitest.cybersource.com' 
            : 'https://api.cybersource.com';
    }

    /**
     * Generate CyberSource Flex Microform capture context
     * This is used for secure credit card tokenization
     */
    public function generateCaptureContext(array $targetOrigins = []): array
    {
        $resource = '/microform/v2/sessions';
        $payload = [
            'targetOrigins' => $targetOrigins ?: [config('app.frontend_url', 'http://localhost:5173')],
            'clientVersion' => 'v2.0',
            'allowedCardNetworks' => ['VISA', 'MASTERCARD', 'AMEX', 'DISCOVER'],
            'allowedPaymentTypes' => ['PANENTRY'],
        ];

        $response = $this->sendRequest('POST', $resource, $payload);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return [
            'success' => true,
            'captureContext' => $response,
        ];
    }

    /**
     * Process a payment with a transient token from Flex Microform
     */
    public function processPayment(array $data): array
    {
        $resource = '/pts/v2/payments';
        
        $payload = [
            'clientReferenceInformation' => [
                'code' => $data['order_id'] ?? uniqid('ORD-'),
            ],
            'processingInformation' => [
                'capture' => true, // Authorize and capture in one step
                'commerceIndicator' => 'internet',
            ],
            'paymentInformation' => [
                'card' => [
                    'number' => $data['card_number'] ?? null,
                    'expirationMonth' => $data['expiration_month'] ?? null,
                    'expirationYear' => $data['expiration_year'] ?? null,
                    'securityCode' => $data['cvv'] ?? null,
                ],
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => number_format($data['amount'], 2, '.', ''),
                    'currency' => $data['currency'] ?? 'AUD',
                ],
                'billTo' => [
                    'firstName' => $data['billing']['first_name'] ?? 'Customer',
                    'lastName' => $data['billing']['last_name'] ?? 'Name',
                    'address1' => $data['billing']['address'] ?? '',
                    'locality' => $data['billing']['city'] ?? '',
                    'administrativeArea' => $data['billing']['state'] ?? '',
                    'postalCode' => $data['billing']['postal_code'] ?? '',
                    'country' => $data['billing']['country'] ?? 'AU',
                    'email' => $data['billing']['email'] ?? '',
                ],
            ],
        ];

        // If using transient token from Flex Microform
        if (!empty($data['transient_token'])) {
            $payload['tokenInformation'] = [
                'transientTokenJwt' => $data['transient_token'],
            ];
            unset($payload['paymentInformation']['card']);
        }

        $response = $this->sendRequest('POST', $resource, $payload);

        if (isset($response['error'])) {
            Log::error('CyberSource payment error', [
                'error' => $response['error'],
                'order_id' => $data['order_id'] ?? null,
            ]);
            return [
                'success' => false,
                'error' => $response['error'],
            ];
        }

        $status = $response['status'] ?? '';
        $isSuccessful = in_array($status, ['AUTHORIZED', 'PENDING', 'AUTHORIZED_PENDING_REVIEW']);

        return [
            'success' => $isSuccessful,
            'transaction_id' => $response['id'] ?? null,
            'status' => $status,
            'message' => $response['message'] ?? ($isSuccessful ? 'Payment successful' : 'Payment failed'),
            'authorization_code' => $response['processorInformation']['approvalCode'] ?? null,
            'response_code' => $response['processorInformation']['responseCode'] ?? null,
            'raw_response' => $response,
        ];
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $transactionId, float $amount, string $currency = 'AUD'): array
    {
        $resource = "/pts/v2/payments/{$transactionId}/refunds";
        
        $payload = [
            'clientReferenceInformation' => [
                'code' => uniqid('REF-'),
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => number_format($amount, 2, '.', ''),
                    'currency' => $currency,
                ],
            ],
        ];

        $response = $this->sendRequest('POST', $resource, $payload);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        $status = $response['status'] ?? '';
        $isSuccessful = $status === 'PENDING';

        return [
            'success' => $isSuccessful,
            'refund_id' => $response['id'] ?? null,
            'status' => $status,
            'message' => $isSuccessful ? 'Refund processed successfully' : 'Refund failed',
        ];
    }

    /**
     * Void/cancel a payment
     */
    public function voidPayment(string $transactionId): array
    {
        $resource = "/pts/v2/payments/{$transactionId}/voids";
        
        $payload = [
            'clientReferenceInformation' => [
                'code' => uniqid('VOID-'),
            ],
        ];

        $response = $this->sendRequest('POST', $resource, $payload);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        $status = $response['status'] ?? '';
        $isSuccessful = $status === 'VOIDED';

        return [
            'success' => $isSuccessful,
            'void_id' => $response['id'] ?? null,
            'status' => $status,
        ];
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails(string $transactionId): array
    {
        $resource = "/tss/v2/transactions/{$transactionId}";
        
        $response = $this->sendRequest('GET', $resource);

        if (isset($response['error'])) {
            return ['success' => false, 'error' => $response['error']];
        }

        return [
            'success' => true,
            'transaction' => $response,
        ];
    }

    /**
     * Send HTTP request to CyberSource API with authentication
     */
    private function sendRequest(string $method, string $resource, array $payload = []): array
    {
        try {
            $host = str_replace(['https://', 'http://'], '', $this->baseUrl);
            $date = gmdate('D, d M Y H:i:s T');
            $payloadJson = !empty($payload) ? json_encode($payload) : '';
            
            // Generate digest for POST/PUT requests
            $digest = '';
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($payloadJson)) {
                $digest = 'SHA-256=' . base64_encode(hash('sha256', $payloadJson, true));
            }

            // Build signature string
            $signatureHeaders = $this->buildSignatureHeaders($method, $resource, $host, $date, $digest);
            $signature = $this->generateSignature($signatureHeaders);

            // Build authorization header
            $headersToSign = $method === 'GET' 
                ? 'host date (request-target) v-c-merchant-id' 
                : 'host date (request-target) digest v-c-merchant-id';
            
            $authorization = sprintf(
                'Signature keyid="%s", algorithm="HmacSHA256", headers="%s", signature="%s"',
                $this->apiKeyId,
                $headersToSign,
                $signature
            );

            $headers = [
                'Host' => $host,
                'Date' => $date,
                'v-c-merchant-id' => $this->merchantId,
                'Content-Type' => 'application/json',
                'Signature' => $authorization,
            ];

            if (!empty($digest)) {
                $headers['Digest'] = $digest;
            }

            $httpClient = Http::withHeaders($headers)
                ->timeout(30);

            $url = $this->baseUrl . $resource;

            if ($method === 'GET') {
                $response = $httpClient->get($url);
            } else {
                $response = $httpClient->withBody($payloadJson, 'application/json')
                    ->post($url);
            }

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            $errorBody = $response->json() ?? [];
            $errorMessage = $errorBody['message'] ?? $errorBody['reason'] ?? 'Unknown error';
            
            Log::error('CyberSource API error', [
                'status' => $response->status(),
                'body' => $errorBody,
                'resource' => $resource,
            ]);

            return ['error' => $errorMessage];

        } catch (\Exception $e) {
            Log::error('CyberSource API exception', [
                'message' => $e->getMessage(),
                'resource' => $resource,
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Build signature headers string
     */
    private function buildSignatureHeaders(string $method, string $resource, string $host, string $date, string $digest): string
    {
        $requestTarget = strtolower($method) . ' ' . $resource;
        
        $headers = "host: {$host}\ndate: {$date}\n(request-target): {$requestTarget}";
        
        if (!empty($digest)) {
            $headers .= "\ndigest: {$digest}";
        }
        
        $headers .= "\nv-c-merchant-id: {$this->merchantId}";
        
        return $headers;
    }

    /**
     * Generate HMAC-SHA256 signature
     */
    private function generateSignature(string $signatureString): string
    {
        $decodedKey = base64_decode($this->secretKey);
        $hash = hash_hmac('sha256', $signatureString, $decodedKey, true);
        return base64_encode($hash);
    }

    /**
     * Check if CyberSource is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) 
            && !empty($this->apiKeyId) 
            && !empty($this->secretKey);
    }
}
