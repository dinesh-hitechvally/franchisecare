<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private bool $sandbox;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id', '');
        $this->clientSecret = config('services.paypal.client_secret', '');
        $this->sandbox = config('services.paypal.sandbox', true);
        $this->baseUrl = $this->sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    /**
     * Get an OAuth2 access token via client credentials
     */
    private function getAccessToken(): ?string
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            Log::error('PayPal token request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Create a PayPal order for the given amount. The returned order id is what the
     * frontend's Smart Buttons SDK approves, and what captureOrder() later settles.
     */
    public function createOrder(float $amount, string $currency, string $referenceId): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to authenticate with PayPal'];
        }

        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $referenceId,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]],
            ]);

        if ($response->failed()) {
            Log::error('PayPal create order failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Failed to create PayPal order'];
        }

        $body = $response->json();

        return [
            'success' => true,
            'order_id' => $body['id'] ?? null,
            'status' => $body['status'] ?? null,
        ];
    }

    /**
     * Capture a PayPal order the customer has already approved via the Smart Buttons flow
     */
    public function captureOrder(string $orderId): array
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to authenticate with PayPal'];
        }

        $response = Http::withToken($accessToken)
            ->post($this->baseUrl . "/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            Log::error('PayPal capture order failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'order_id' => $orderId,
            ]);
            return ['success' => false, 'error' => $response->json()['message'] ?? 'Failed to capture PayPal payment'];
        }

        $body = $response->json();
        $status = $body['status'] ?? '';
        $capture = $body['purchase_units'][0]['payments']['captures'][0] ?? null;
        $isSuccessful = $status === 'COMPLETED' && ($capture['status'] ?? null) === 'COMPLETED';

        return [
            'success' => $isSuccessful,
            'capture_id' => $capture['id'] ?? null,
            'status' => $status,
            'raw_response' => $body,
        ];
    }

    /**
     * Check if PayPal is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}
