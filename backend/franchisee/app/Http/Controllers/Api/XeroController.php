<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\XeroConnection;
use App\Services\XeroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XeroController extends Controller
{
    protected XeroService $xeroService;

    public function __construct(XeroService $xeroService)
    {
        $this->xeroService = $xeroService;
    }

    /**
     * Get the current Xero connection status
     */
    public function status()
    {
        $connection = XeroConnection::where('company_id', auth()->user()->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return response()->json([
                'connected' => false,
                'message' => 'Not connected to Xero',
            ]);
        }

        try {
            $organization = $this->xeroService->getOrganization($connection);

            return response()->json([
                'connected' => true,
                'tenant_name' => $connection->tenant_name,
                'organization' => $organization['Name'] ?? $connection->tenant_name,
                'last_synced_at' => $connection->last_synced_at?->toISOString(),
            ]);
        } catch (\Exception $e) {
            // Token might be invalid
            return response()->json([
                'connected' => false,
                'message' => 'Connection expired, please reconnect',
            ]);
        }
    }

    /**
     * Get the authorization URL to start OAuth flow
     */
    public function authorize()
    {
        $state = Str::random(40);
        
        // Store state in session/cache for validation
        cache()->put('xero_oauth_state_' . auth()->id(), $state, now()->addMinutes(10));

        $authUrl = $this->xeroService->getAuthorizationUrl($state);

        return response()->json([
            'auth_url' => $authUrl,
        ]);
    }

    /**
     * Handle OAuth callback from Xero
     */
    public function callback(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        // Validate state
        $storedState = cache()->get('xero_oauth_state_' . auth()->id());
        if ($storedState !== $request->state) {
            return response()->json(['error' => 'Invalid state parameter'], 400);
        }

        cache()->forget('xero_oauth_state_' . auth()->id());

        try {
            // Exchange code for tokens
            $tokens = $this->xeroService->exchangeCodeForToken($request->code);

            // Get connected tenants
            $connections = $this->xeroService->getConnections($tokens['access_token']);

            if (empty($connections)) {
                return response()->json(['error' => 'No Xero organizations found'], 400);
            }

            // Use the first tenant (or let user choose in future)
            $tenant = $connections[0];

            // Save or update connection
            $connection = XeroConnection::updateOrCreate(
                [
                    'company_id' => auth()->user()->company_id,
                ],
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

            return response()->json([
                'success' => true,
                'message' => 'Successfully connected to Xero',
                'tenant_name' => $tenant['tenantName'],
            ]);

        } catch (\Exception $e) {
            Log::error('Xero OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to connect to Xero: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect from Xero
     */
    public function disconnect()
    {
        XeroConnection::where('company_id', auth()->user()->company_id)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Disconnected from Xero',
        ]);
    }

    /**
     * Get Xero accounts for mapping
     */
    public function accounts()
    {
        $connection = XeroConnection::where('company_id', auth()->user()->company_id)
            ->active()
            ->firstOrFail();

        try {
            $accounts = $this->xeroService->getAccounts($connection);

            // Filter to relevant account types
            $filteredAccounts = collect($accounts)->filter(function ($account) {
                return in_array($account['Type'], ['REVENUE', 'SALES', 'BANK', 'EXPENSE']);
            })->values();

            return response()->json([
                'accounts' => $filteredAccounts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually sync a booking to Xero
     */
    public function syncBooking(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $connection = XeroConnection::where('company_id', auth()->user()->company_id)
            ->active()
            ->firstOrFail();

        try {
            $booking = \App\Models\Booking::with(['customer', 'services'])
                ->where('company_id', auth()->user()->company_id)
                ->findOrFail($request->booking_id);

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

            // Update connection last synced
            $connection->update(['last_synced_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Booking synced to Xero',
                'invoice_id' => $result['Invoices'][0]['InvoiceID'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Xero sync booking failed', [
                'booking_id' => $request->booking_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to sync booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test the Xero connection
     */
    public function test()
    {
        $connection = XeroConnection::where('company_id', auth()->user()->company_id)
            ->active()
            ->first();

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'Not connected to Xero',
            ]);
        }

        try {
            $organization = $this->xeroService->getOrganization($connection);

            return response()->json([
                'success' => true,
                'message' => 'Connection successful',
                'organization' => $organization['Name'] ?? 'Unknown',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ]);
        }
    }
}
