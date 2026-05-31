<?php

namespace App\Services;

use App\Models\XeroConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XeroService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;
    protected string $scopes;
    protected string $authorizeUrl = 'https://login.xero.com/identity/connect/authorize';
    protected string $tokenUrl = 'https://identity.xero.com/connect/token';
    protected string $apiBaseUrl = 'https://api.xero.com/api.xro/2.0';
    protected string $connectionsUrl = 'https://api.xero.com/connections';

    public function __construct()
    {
        $this->clientId = config('services.xero.client_id');
        $this->clientSecret = config('services.xero.client_secret');
        $this->redirectUri = config('services.xero.redirect_uri');
        $this->scopes = config('services.xero.scopes');
    }

    /**
     * Get the authorization URL for OAuth flow
     */
    public function getAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scopes,
            'state' => $state,
        ]);

        return $this->authorizeUrl . '?' . $params;
    }

    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->tokenUrl, [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $this->redirectUri,
            ]);

        if ($response->failed()) {
            Log::error('Xero token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to exchange code for token: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Refresh the access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post($this->tokenUrl, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        if ($response->failed()) {
            Log::error('Xero token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to refresh token');
        }

        return $response->json();
    }

    /**
     * Get connected tenants (organizations)
     */
    public function getConnections(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get($this->connectionsUrl);

        if ($response->failed()) {
            throw new \Exception('Failed to get Xero connections');
        }

        return $response->json();
    }

    /**
     * Ensure the access token is valid, refresh if needed
     */
    public function ensureValidToken(XeroConnection $connection): string
    {
        if ($connection->isExpired()) {
            $tokens = $this->refreshToken($connection->refresh_token);
            
            $connection->update([
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_at' => now()->addSeconds($tokens['expires_in']),
            ]);
        }

        return $connection->access_token;
    }

    /**
     * Create an invoice in Xero
     */
    public function createInvoice(XeroConnection $connection, array $invoiceData): array
    {
        $accessToken = $this->ensureValidToken($connection);

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Xero-Tenant-Id' => $connection->tenant_id,
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiBaseUrl . '/Invoices', [
                'Invoices' => [$invoiceData],
            ]);

        if ($response->failed()) {
            Log::error('Xero create invoice failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create invoice in Xero: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Create a contact in Xero
     */
    public function createContact(XeroConnection $connection, array $contactData): array
    {
        $accessToken = $this->ensureValidToken($connection);

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Xero-Tenant-Id' => $connection->tenant_id,
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiBaseUrl . '/Contacts', [
                'Contacts' => [$contactData],
            ]);

        if ($response->failed()) {
            Log::error('Xero create contact failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create contact in Xero');
        }

        return $response->json();
    }

    /**
     * Find or create a contact by email
     */
    public function findOrCreateContact(XeroConnection $connection, array $contactData): array
    {
        $accessToken = $this->ensureValidToken($connection);

        // Try to find existing contact by email
        if (!empty($contactData['EmailAddress'])) {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Xero-Tenant-Id' => $connection->tenant_id])
                ->get($this->apiBaseUrl . '/Contacts', [
                    'where' => 'EmailAddress=="' . $contactData['EmailAddress'] . '"',
                ]);

            if ($response->successful()) {
                $contacts = $response->json()['Contacts'] ?? [];
                if (!empty($contacts)) {
                    return $contacts[0];
                }
            }
        }

        // Create new contact
        $result = $this->createContact($connection, $contactData);
        return $result['Contacts'][0] ?? [];
    }

    /**
     * Create a payment in Xero
     */
    public function createPayment(XeroConnection $connection, array $paymentData): array
    {
        $accessToken = $this->ensureValidToken($connection);

        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Xero-Tenant-Id' => $connection->tenant_id,
                'Content-Type' => 'application/json',
            ])
            ->post($this->apiBaseUrl . '/Payments', [
                'Payments' => [$paymentData],
            ]);

        if ($response->failed()) {
            Log::error('Xero create payment failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to create payment in Xero');
        }

        return $response->json();
    }

    /**
     * Get accounts from Xero
     */
    public function getAccounts(XeroConnection $connection): array
    {
        $accessToken = $this->ensureValidToken($connection);

        $response = Http::withToken($accessToken)
            ->withHeaders(['Xero-Tenant-Id' => $connection->tenant_id])
            ->get($this->apiBaseUrl . '/Accounts');

        if ($response->failed()) {
            throw new \Exception('Failed to get accounts from Xero');
        }

        return $response->json()['Accounts'] ?? [];
    }

    /**
     * Get organization info
     */
    public function getOrganization(XeroConnection $connection): array
    {
        $accessToken = $this->ensureValidToken($connection);

        $response = Http::withToken($accessToken)
            ->withHeaders(['Xero-Tenant-Id' => $connection->tenant_id])
            ->get($this->apiBaseUrl . '/Organisation');

        if ($response->failed()) {
            throw new \Exception('Failed to get organization from Xero');
        }

        return $response->json()['Organisations'][0] ?? [];
    }

    /**
     * Sync a booking payment to Xero as invoice + payment
     */
    public function syncBookingPayment(XeroConnection $connection, array $bookingData): array
    {
        // Find or create contact
        $contact = $this->findOrCreateContact($connection, [
            'Name' => $bookingData['customer_name'],
            'EmailAddress' => $bookingData['customer_email'] ?? null,
            'Phones' => !empty($bookingData['customer_phone']) ? [
                ['PhoneType' => 'DEFAULT', 'PhoneNumber' => $bookingData['customer_phone']]
            ] : [],
        ]);

        // Create invoice
        $invoice = $this->createInvoice($connection, [
            'Type' => 'ACCREC',
            'Contact' => ['ContactID' => $contact['ContactID']],
            'Date' => $bookingData['date'] ?? now()->format('Y-m-d'),
            'DueDate' => $bookingData['date'] ?? now()->format('Y-m-d'),
            'LineItems' => [
                [
                    'Description' => $bookingData['description'] ?? 'Service Booking',
                    'Quantity' => 1,
                    'UnitAmount' => $bookingData['amount'],
                    'AccountCode' => $bookingData['account_code'] ?? '200', // Default sales account
                ],
            ],
            'Status' => 'AUTHORISED',
            'Reference' => $bookingData['reference'] ?? null,
        ]);

        $invoiceId = $invoice['Invoices'][0]['InvoiceID'] ?? null;

        // Create payment if invoice created
        if ($invoiceId && ($bookingData['paid'] ?? false)) {
            $this->createPayment($connection, [
                'Invoice' => ['InvoiceID' => $invoiceId],
                'Account' => ['Code' => $bookingData['payment_account_code'] ?? '090'], // Default bank account
                'Date' => $bookingData['payment_date'] ?? now()->format('Y-m-d'),
                'Amount' => $bookingData['amount'],
                'Reference' => $bookingData['payment_reference'] ?? 'Online Payment',
            ]);
        }

        return $invoice;
    }
}
