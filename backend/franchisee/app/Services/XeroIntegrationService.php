<?php

namespace App\Services;

use App\Contracts\Services\XeroIntegrationServiceInterface;
use App\Models\Booking;
use App\Models\User;
use App\Models\XeroConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XeroIntegrationService implements XeroIntegrationServiceInterface
{
    public function __construct(
        protected XeroService $xeroService
    ) {}

    public function status(User $user): array
    {
        $connection = XeroConnection::where('company_id', $user->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'connected' => false,
                'message' => 'Not connected to Xero',
            ];
        }

        try {
            $organization = $this->xeroService->getOrganization($connection);

            return [
                'connected' => true,
                'tenant_name' => $connection->tenant_name,
                'organization' => $organization['Name'] ?? $connection->tenant_name,
                'last_synced_at' => $connection->last_synced_at?->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'message' => 'Connection expired, please reconnect',
            ];
        }
    }

    public function authorize(User $user): array
    {
        $state = Str::random(40);

        Cache::put('xero_oauth_state_' . $user->id, $state, now()->addMinutes(10));

        $authUrl = $this->xeroService->getAuthorizationUrl($state);

        return [
            'auth_url' => $authUrl,
        ];
    }

    public function callback(User $user, string $code, string $state): array
    {
        $storedState = Cache::get('xero_oauth_state_' . $user->id);
        if ($storedState !== $state) {
            return [
                'success' => false,
                'error' => 'Invalid state parameter',
                'status_code' => 400,
            ];
        }

        Cache::forget('xero_oauth_state_' . $user->id);

        try {
            $tokens = $this->xeroService->exchangeCodeForToken($code);
            $connections = $this->xeroService->getConnections($tokens['access_token']);

            if (empty($connections)) {
                return [
                    'success' => false,
                    'error' => 'No Xero organizations found',
                    'status_code' => 400,
                ];
            }

            $tenant = $connections[0];

            XeroConnection::updateOrCreate(
                ['company_id' => $user->company_id],
                [
                    'tenant_id' => $tenant['tenantId'],
                    'tenant_name' => $tenant['tenantName'],
                    'tenant_type' => $tenant['tenantType'],
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_at' => now()->addSeconds($tokens['expires_in']),
                    'is_active' => true,
                ]
            );

            return [
                'success' => true,
                'message' => 'Successfully connected to Xero',
                'tenant_name' => $tenant['tenantName'],
            ];

        } catch (\Exception $e) {
            Log::error('Xero OAuth callback failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Failed to connect to Xero: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function disconnect(User $user): array
    {
        XeroConnection::where('company_id', $user->company_id)
            ->update(['is_active' => false]);

        return [
            'success' => true,
            'message' => 'Disconnected from Xero',
        ];
    }

    public function accounts(User $user): array
    {
        $connection = XeroConnection::where('company_id', $user->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        try {
            $accounts = $this->xeroService->getAccounts($connection);

            $filteredAccounts = collect($accounts)->filter(function ($account) {
                return in_array($account['Type'], ['REVENUE', 'SALES', 'BANK', 'EXPENSE']);
            })->values();

            return [
                'success' => true,
                'accounts' => $filteredAccounts,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch accounts: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function syncBooking(User $user, int $bookingId): array
    {
        $connection = XeroConnection::where('company_id', $user->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        try {
            $booking = Booking::with(['customer', 'services'])
                ->where('company_id', $user->company_id)
                ->findOrFail($bookingId);

            $description = $booking->services->pluck('name')->implode(', ') ?: 'Service Booking';

            $result = $this->xeroService->syncBookingPayment($connection, [
                'customer_name' => $booking->customer->name ?? 'Customer',
                'customer_email' => $booking->customer->email ?? null,
                'customer_phone' => $booking->customer->phone ?? null,
                'amount' => $booking->total,
                'description' => $description,
                'reference' => 'Booking #' . $booking->id,
                'date' => $booking->date->format('Y-m-d'),
                'paid' => $booking->status === 'completed' && $booking->payment_status === 'paid',
                'payment_date' => $booking->updated_at->format('Y-m-d'),
                'payment_reference' => 'Payment for Booking #' . $booking->id,
            ]);

            $connection->update(['last_synced_at' => now()]);

            return [
                'success' => true,
                'message' => 'Booking synced to Xero',
                'invoice_id' => $result['Invoices'][0]['InvoiceID'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Xero sync booking failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to sync booking: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function test(User $user): array
    {
        $connection = XeroConnection::where('company_id', $user->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'message' => 'Not connected to Xero',
            ];
        }

        try {
            $organization = $this->xeroService->getOrganization($connection);

            return [
                'success' => true,
                'message' => 'Connection successful',
                'organization' => $organization['Name'] ?? 'Unknown',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ];
        }
    }
}
