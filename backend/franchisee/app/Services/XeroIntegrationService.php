<?php

namespace App\Services;

use App\Contracts\Services\XeroIntegrationServiceInterface;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\InventoryOrder;
use App\Models\PaymentTransaction;
use App\Models\Service;
use App\Models\User;
use App\Models\XeroBill;
use App\Models\XeroConnection;
use App\Models\XeroContact;
use App\Models\XeroInvoice;
use App\Models\XeroItem;
use App\Models\XeroOauthRequest;
use App\Models\XeroSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XeroIntegrationService implements XeroIntegrationServiceInterface
{
    public function __construct(
        protected XeroService $xeroService
    ) {}

    /**
     * Resolve this company's configured Xero account codes/defaults, falling back
     * to the global config values for anything the company hasn't set yet.
     */
    protected function xeroSettings(int $companyId): array
    {
        return XeroSetting::resolveForCompany($companyId);
    }

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

        XeroOauthRequest::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'state' => $state,
            'status' => 'pending',
        ]);

        Log::debug('Xero authorize: state stored', ['user_id' => $user->id, 'state' => $state]);

        $authUrl = $this->xeroService->getAuthorizationUrl($state);

        return [
            'auth_url' => $authUrl,
        ];
    }

    public function callback(User $user, string $code, string $state, ?string $scope = null): array
    {
        $oauthRequest = XeroOauthRequest::where('state', $state)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        Log::debug('Xero callback: state comparison', [
            'user_id' => $user->id,
            'received_state' => $state,
            'received_scope' => $scope,
            'matched_request_id' => $oauthRequest?->id,
        ]);

        if (!$oauthRequest) {
            return [
                'success' => false,
                'error' => 'Invalid state parameter',
                'status_code' => 400,
            ];
        }

        if ($oauthRequest->created_at->lt(now()->subMinutes(10))) {
            $oauthRequest->update(['status' => 'failed', 'error' => 'State expired']);

            return [
                'success' => false,
                'error' => 'State parameter expired',
                'status_code' => 400,
            ];
        }

        $oauthRequest->update([
            'code' => $code,
            'scope' => $scope,
        ]);

        try {
            $tokens = $this->xeroService->exchangeCodeForToken($code);
            $connections = $this->xeroService->getConnections($tokens['access_token']);

            if (empty($connections)) {
                $oauthRequest->update(['status' => 'failed', 'error' => 'No Xero organizations found']);

                return [
                    'success' => false,
                    'error' => 'No Xero organizations found',
                    'status_code' => 400,
                ];
            }

            $tenant = $connections[0];
            $tenantScope = $scope ?? $tokens['scope'] ?? null;

            $oauthRequest->update([
                'scope' => $tenantScope,
                'status' => 'completed',
            ]);

            XeroConnection::updateOrCreate(
                ['company_id' => $user->company_id],
                [
                    'tenant_id' => $tenant['tenantId'],
                    'tenant_name' => $tenant['tenantName'],
                    'tenant_type' => $tenant['tenantType'],
                    'xero_oauth_request_id' => $oauthRequest->id,
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
            $oauthRequest->update(['status' => 'failed', 'error' => $e->getMessage()]);

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

            $activeAccounts = collect($accounts)->filter(function ($account) {
                return ($account['Status'] ?? null) === 'ACTIVE';
            })->values();

            return [
                'success' => true,
                'accounts' => $activeAccounts,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch accounts: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function createAccount(User $user, array $data): array
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
            $result = $this->xeroService->createAccount($connection, [
                'Code' => $data['code'],
                'Name' => $data['name'],
                'Type' => $data['type'],
            ]);

            $connection->update(['last_synced_at' => now()]);

            return [
                'success' => true,
                'message' => 'Account created in Xero',
                'account' => $result['Accounts'][0] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to create account: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function taxRates(User $user): array
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
            $taxRates = $this->xeroService->getTaxRates($connection);

            $activeTaxRates = collect($taxRates)->filter(function ($taxRate) {
                return ($taxRate['Status'] ?? null) === 'ACTIVE';
            })->values();

            return [
                'success' => true,
                'tax_rates' => $activeTaxRates,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Failed to fetch tax rates: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function getSettings(User $user): array
    {
        return [
            'success' => true,
            'settings' => XeroSetting::resolveForCompany($user->company_id),
        ];
    }

    public function updateSettings(User $user, array $data): array
    {
        XeroSetting::updateOrCreate(
            ['company_id' => $user->company_id],
            array_intersect_key($data, XeroSetting::defaults())
        );

        return [
            'success' => true,
            'message' => 'Xero settings updated',
            'settings' => XeroSetting::resolveForCompany($user->company_id),
        ];
    }

    public function syncBooking(User $user, int $bookingId): array
    {
        return $this->syncBookingForCompany($user->company_id, $bookingId);
    }

    protected function syncBookingForCompany(int $companyId, int $bookingId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
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
            $booking = Booking::with(['customer', 'details.service'])
                ->where('company_id', $companyId)
                ->findOrFail($bookingId);

            $xeroInvoice = XeroInvoice::firstOrNew([
                'company_id' => $companyId,
                'reference_type' => 'booking',
                'reference_id' => $booking->id,
            ]);

            // A completed booking means the service was delivered and paid for, even when no
            // PaymentTransaction exists (e.g. cash/in-person payment recorded via status only).
            $isPaid = $booking->status === 'completed' || PaymentTransaction::where('reference_type', 'booking')
                ->where('reference_id', $booking->id)
                ->where('status', 'completed')
                ->exists();

            // Invoice already exists in Xero - never recreate it, only ever add a Payment or void it
            if ($xeroInvoice->exists && $xeroInvoice->xero_invoice_id) {
                if ($booking->status === 'cancelled') {
                    if (in_array($xeroInvoice->status, ['voided', 'needs_manual_review'])) {
                        return [
                            'success' => true,
                            'message' => 'Booking cancellation already handled in Xero',
                            'invoice_id' => $xeroInvoice->xero_invoice_id,
                        ];
                    }

                    if ($xeroInvoice->paid_at) {
                        // Xero refuses to void an invoice with a payment applied - this needs
                        // a manual credit note/refund decision, not an automatic action.
                        $xeroInvoice->update([
                            'status' => 'needs_manual_review',
                            'error' => 'Booking cancelled after the invoice was already paid - requires a manual credit note/refund in Xero',
                        ]);

                        return [
                            'success' => false,
                            'error' => 'Booking cancelled after payment - needs a manual credit note/refund in Xero',
                            'status_code' => 422,
                        ];
                    }

                    $this->xeroService->voidInvoice($connection, $xeroInvoice->xero_invoice_id);

                    $connection->update(['last_synced_at' => now()]);

                    $xeroInvoice->update(['status' => 'voided', 'error' => null]);

                    return [
                        'success' => true,
                        'message' => 'Xero invoice voided for cancelled booking',
                        'invoice_id' => $xeroInvoice->xero_invoice_id,
                    ];
                }

                if ($xeroInvoice->paid_at) {
                    return [
                        'success' => true,
                        'message' => 'Booking already synced and paid',
                        'invoice_id' => $xeroInvoice->xero_invoice_id,
                    ];
                }

                if (!$isPaid) {
                    return [
                        'success' => true,
                        'message' => 'Booking invoice already exists in Xero, still awaiting payment',
                        'invoice_id' => $xeroInvoice->xero_invoice_id,
                    ];
                }

                $this->xeroService->createPayment($connection, [
                    'Invoice' => ['InvoiceID' => $xeroInvoice->xero_invoice_id],
                    'Account' => ['Code' => $this->xeroSettings($companyId)['bank_account_code']],
                    'Date' => now()->format('Y-m-d'),
                    'Amount' => $booking->total,
                    'Reference' => 'Payment for Booking #' . $booking->id,
                ]);

                $connection->update(['last_synced_at' => now()]);

                $xeroInvoice->update(['paid_at' => now(), 'error' => null]);

                return [
                    'success' => true,
                    'message' => 'Payment recorded on existing Xero invoice',
                    'invoice_id' => $xeroInvoice->xero_invoice_id,
                ];
            }

            // Never invoiced and now cancelled - nothing to create, nothing to void. Still record
            // this in xero_invoices (status 'skipped') so its updated_at catches up past the
            // booking's - otherwise this booking would show up as "pending" on every cron run
            // forever, since there'd be no xero_invoices row for the eligibility check to compare against.
            if ($booking->status === 'cancelled') {
                $xeroInvoice->invoice_type = 'ACCREC';
                $xeroInvoice->amount = $booking->total;
                $xeroInvoice->status = 'skipped';
                $xeroInvoice->error = null;
                $xeroInvoice->save();

                return [
                    'success' => true,
                    'message' => 'Booking cancelled before being synced to Xero, nothing to do',
                ];
            }

            if (!$booking->customer) {
                $xeroInvoice->invoice_type = 'ACCREC';
                $xeroInvoice->amount = $booking->total;
                $xeroInvoice->status = 'failed';
                $xeroInvoice->error = 'Booking has no linked customer';
                $xeroInvoice->save();

                return [
                    'success' => false,
                    'error' => 'Booking has no linked customer, cannot sync to Xero',
                    'status_code' => 422,
                ];
            }

            // No invoice yet - create it (Customer must be synced to Xero as a Contact first)
            $xeroInvoice->invoice_type = 'ACCREC';
            $xeroInvoice->amount = $booking->total;
            $xeroInvoice->status = 'pending';
            $xeroInvoice->error = null;
            $xeroInvoice->save();

            $contactId = $this->syncCustomerContact($companyId, $connection, $booking->customer);
            $settings = $this->xeroSettings($companyId);

            $description = $booking->details->pluck('service.name')->filter()->implode(', ') ?: 'Service Booking';

            $result = $this->xeroService->syncBookingPayment($connection, [
                'contact_id' => $contactId,
                'customer_name' => trim($booking->customer->first_name . ' ' . $booking->customer->last_name) ?: 'Customer',
                'customer_email' => $booking->customer->email ?? null,
                'customer_phone' => $booking->customer->phone ?? null,
                'amount' => $booking->total,
                'description' => $description,
                'reference' => 'Booking #' . $booking->id,
                'account_code' => $settings['service_sales_account_code'],
                'date' => $booking->start_date->format('Y-m-d'),
                'paid' => $isPaid,
                'payment_date' => $booking->updated_at->format('Y-m-d'),
                'payment_account_code' => $settings['bank_account_code'],
                'payment_reference' => 'Payment for Booking #' . $booking->id,
            ]);

            $connection->update(['last_synced_at' => now()]);

            $xeroInvoice->update([
                'xero_invoice_id' => $result['Invoices'][0]['InvoiceID'] ?? null,
                'xero_invoice_number' => $result['Invoices'][0]['InvoiceNumber'] ?? null,
                'status' => 'synced',
                'synced_at' => now(),
                'paid_at' => $isPaid ? now() : null,
            ]);

            return [
                'success' => true,
                'message' => 'Booking synced to Xero',
                'invoice_id' => $xeroInvoice->xero_invoice_id,
            ];

        } catch (\Exception $e) {
            Log::error('Xero sync booking failed', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage(),
            ]);

            if (isset($xeroInvoice)) {
                $xeroInvoice->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'Failed to sync booking: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function pushPendingBookings(User $user): array
    {
        return $this->pushPendingBookingsForCompany($user->company_id);
    }

    /**
     * Push every not-yet-synced booking to Xero for every company with an active connection.
     * Used by the unauthenticated cron endpoint (no single User/company context available).
     */
    public function pushPendingBookingsForAllCompanies(): array
    {
        $companyIds = XeroConnection::active()->pluck('company_id');

        $perCompany = $companyIds->map(function ($companyId) {
            return array_merge(['company_id' => $companyId], $this->pushPendingBookingsForCompany($companyId));
        });

        return $this->summarizeAcrossCompanies($perCompany);
    }

    protected function pushPendingBookingsForCompany(int $companyId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        // A booking needs (re)checking if it has never been invoiced yet, or if it has changed
        // since we last touched its xero_invoices row - that row's updated_at only moves when
        // our own code writes to it (create, mark paid, void, flag for review, etc.), so once
        // it catches up past the booking's updated_at, the booking is left alone until it
        // changes again (status flip, cancellation, etc. all bump bookings.updated_at).
        // syncBookingForCompany() itself decides the right action from the booking's current
        // status - create, add a payment on an existing invoice, void it, or no-op.
        $pendingBookingIds = Booking::where('bookings.company_id', $companyId)
            ->leftJoin('xero_invoices', function ($join) {
                $join->on('xero_invoices.reference_id', '=', 'bookings.id')
                    ->where('xero_invoices.reference_type', '=', 'booking');
            })
            ->where(function ($query) {
                $query->whereNull('xero_invoices.id')
                    ->orWhereColumn('bookings.updated_at', '>', 'xero_invoices.updated_at');
            })
            ->pluck('bookings.id');

        $results = $pendingBookingIds->map(function ($bookingId) use ($companyId) {
            return array_merge(['booking_id' => $bookingId], $this->syncBookingForCompany($companyId, $bookingId));
        });

        return [
            'success' => true,
            'total' => $results->count(),
            'pushed' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'results' => $results->values(),
        ];
    }

    /**
     * Resolve (creating if needed) the Xero Contact for a customer, persisting the mapping.
     * Reused across the customer's synced records so the same Contact is referenced every time.
     */
    protected function syncCustomerContact(int $companyId, XeroConnection $connection, Customer $customer): ?string
    {
        $xeroContact = XeroContact::firstOrNew([
            'company_id' => $companyId,
            'reference_type' => 'customer',
            'reference_id' => $customer->id,
        ]);

        if ($xeroContact->exists && $xeroContact->status === 'synced' && $xeroContact->xero_contact_id) {
            return $xeroContact->xero_contact_id;
        }

        $name = trim($customer->first_name . ' ' . $customer->last_name) ?: 'Customer #' . $customer->id;

        $xeroContact->name = $name;
        $xeroContact->email = $customer->email;
        $xeroContact->status = 'pending';
        $xeroContact->error = null;
        $xeroContact->save();

        try {
            $contact = $this->xeroService->findOrCreateContact($connection, [
                'Name' => $name,
                'EmailAddress' => $customer->email ?? null,
                'Phones' => !empty($customer->phone) ? [
                    ['PhoneType' => 'DEFAULT', 'PhoneNumber' => $customer->phone],
                ] : [],
            ]);

            $xeroContact->update([
                'xero_contact_id' => $contact['ContactID'] ?? null,
                'status' => 'synced',
                'synced_at' => now(),
            ]);

            return $xeroContact->xero_contact_id;
        } catch (\Exception $e) {
            $xeroContact->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncCustomer(User $user, int $customerId): array
    {
        return $this->syncCustomerForCompany($user->company_id, $customerId);
    }

    protected function syncCustomerForCompany(int $companyId, int $customerId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
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
            $customer = Customer::where('company_id', $companyId)->findOrFail($customerId);

            $contactId = $this->syncCustomerContact($companyId, $connection, $customer);

            $connection->update(['last_synced_at' => now()]);

            return [
                'success' => true,
                'message' => 'Customer synced to Xero',
                'contact_id' => $contactId,
            ];
        } catch (\Exception $e) {
            Log::error('Xero sync customer failed', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to sync customer: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function pushPendingCustomers(User $user): array
    {
        return $this->pushPendingCustomersForCompany($user->company_id);
    }

    public function pushPendingCustomersForAllCompanies(): array
    {
        $companyIds = XeroConnection::active()->pluck('company_id');

        $perCompany = $companyIds->map(function ($companyId) {
            return array_merge(['company_id' => $companyId], $this->pushPendingCustomersForCompany($companyId));
        });

        return $this->summarizeAcrossCompanies($perCompany);
    }

    protected function pushPendingCustomersForCompany(int $companyId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        $pendingCustomerIds = Customer::where('customers.company_id', $companyId)
            ->leftJoin('xero_contacts', function ($join) {
                $join->on('xero_contacts.reference_id', '=', 'customers.id')
                    ->where('xero_contacts.reference_type', '=', 'customer')
                    ->where('xero_contacts.status', '=', 'synced');
            })
            ->whereNull('xero_contacts.id')
            ->pluck('customers.id');

        $results = $pendingCustomerIds->map(function ($customerId) use ($companyId) {
            return array_merge(['customer_id' => $customerId], $this->syncCustomerForCompany($companyId, $customerId));
        });

        return [
            'success' => true,
            'total' => $results->count(),
            'pushed' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'results' => $results->values(),
        ];
    }

    /**
     * Resolve (creating if needed) the single default supplier contact used for every
     * internal restocking purchase Bill, since inventory_orders track no real supplier.
     */
    protected function resolveDefaultSupplierContact(int $companyId, XeroConnection $connection): ?string
    {
        $xeroContact = XeroContact::firstOrNew([
            'company_id' => $companyId,
            'reference_type' => 'default_supplier',
            'reference_id' => null,
        ]);

        if ($xeroContact->exists && $xeroContact->status === 'synced' && $xeroContact->xero_contact_id) {
            return $xeroContact->xero_contact_id;
        }

        $name = $this->xeroSettings($companyId)['default_supplier_name'];

        $xeroContact->name = $name;
        $xeroContact->status = 'pending';
        $xeroContact->error = null;
        $xeroContact->save();

        try {
            $contact = $this->xeroService->findOrCreateContactByName($connection, $name, ['IsSupplier' => true]);

            $xeroContact->update([
                'xero_contact_id' => $contact['ContactID'] ?? null,
                'status' => 'synced',
                'synced_at' => now(),
            ]);

            return $xeroContact->xero_contact_id;
        } catch (\Exception $e) {
            $xeroContact->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function syncPurchase(User $user, int $orderId): array
    {
        return $this->syncPurchaseForCompany($user->company_id, $orderId);
    }

    protected function syncPurchaseForCompany(int $companyId, int $orderId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
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
            $order = InventoryOrder::with('items')
                ->where('company_id', $companyId)
                ->findOrFail($orderId);

            $xeroBill = XeroBill::firstOrNew([
                'company_id' => $companyId,
                'reference_type' => 'inventory_order',
                'reference_id' => $order->id,
            ]);
            $xeroBill->amount = $order->total;
            $xeroBill->status = 'pending';
            $xeroBill->error = null;
            $xeroBill->save();

            $contactId = $this->resolveDefaultSupplierContact($companyId, $connection);
            $settings = $this->xeroSettings($companyId);

            $lineItems = $order->items->map(function ($item) use ($settings) {
                return [
                    'Description' => $item->product_name,
                    'Quantity' => $item->quantity,
                    'UnitAmount' => $item->unit_price,
                    'AccountCode' => $settings['inventory_cogs_account_code'],
                ];
            })->values()->all();

            if (empty($lineItems)) {
                $lineItems = [[
                    'Description' => 'Inventory order #' . $order->order_number,
                    'Quantity' => 1,
                    'UnitAmount' => $order->total,
                    'AccountCode' => $settings['inventory_cogs_account_code'],
                ]];
            }

            $billDate = ($order->ordered_at ?? $order->created_at)->format('Y-m-d');

            $result = $this->xeroService->createInvoice($connection, [
                'Type' => 'ACCPAY',
                'Contact' => ['ContactID' => $contactId],
                'Date' => $billDate,
                'DueDate' => $billDate,
                'LineItems' => $lineItems,
                'Status' => 'AUTHORISED',
                'Reference' => 'Purchase Order #' . $order->order_number,
            ]);

            $billId = $result['Invoices'][0]['InvoiceID'] ?? null;
            $billNumber = $result['Invoices'][0]['InvoiceNumber'] ?? null;

            if ($billId && $order->payment_status === 'paid') {
                $this->xeroService->createPayment($connection, [
                    'Invoice' => ['InvoiceID' => $billId],
                    'Account' => ['Code' => $settings['bank_account_code']],
                    'Date' => ($order->paid_at ?? now())->format('Y-m-d'),
                    'Amount' => $order->total,
                    'Reference' => 'Payment for Purchase Order #' . $order->order_number,
                ]);
            }

            $connection->update(['last_synced_at' => now()]);

            $xeroBill->update([
                'xero_invoice_id' => $billId,
                'xero_invoice_number' => $billNumber,
                'status' => 'synced',
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Purchase synced to Xero',
                'bill_id' => $xeroBill->xero_invoice_id,
            ];

        } catch (\Exception $e) {
            Log::error('Xero sync purchase failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            if (isset($xeroBill)) {
                $xeroBill->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'Failed to sync purchase: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function pushPendingPurchases(User $user): array
    {
        return $this->pushPendingPurchasesForCompany($user->company_id);
    }

    public function pushPendingPurchasesForAllCompanies(): array
    {
        $companyIds = XeroConnection::active()->pluck('company_id');

        $perCompany = $companyIds->map(function ($companyId) {
            return array_merge(['company_id' => $companyId], $this->pushPendingPurchasesForCompany($companyId));
        });

        return $this->summarizeAcrossCompanies($perCompany);
    }

    protected function pushPendingPurchasesForCompany(int $companyId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        $pendingOrderIds = InventoryOrder::where('inventory_orders.company_id', $companyId)
            ->where('inventory_orders.status', '!=', 'cancelled')
            ->leftJoin('xero_bills', function ($join) {
                $join->on('xero_bills.reference_id', '=', 'inventory_orders.id')
                    ->where('xero_bills.reference_type', '=', 'inventory_order')
                    ->where('xero_bills.status', '=', 'synced');
            })
            ->whereNull('xero_bills.id')
            ->pluck('inventory_orders.id');

        $results = $pendingOrderIds->map(function ($orderId) use ($companyId) {
            return array_merge(['order_id' => $orderId], $this->syncPurchaseForCompany($companyId, $orderId));
        });

        return [
            'success' => true,
            'total' => $results->count(),
            'pushed' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'results' => $results->values(),
        ];
    }

    public function syncInventoryItem(User $user, int $inventoryItemId): array
    {
        return $this->syncInventoryItemForCompany($user->company_id, $inventoryItemId);
    }

    protected function syncInventoryItemForCompany(int $companyId, int $inventoryItemId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
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
            $item = InventoryItem::where('company_id', $companyId)->findOrFail($inventoryItemId);

            $xeroItem = XeroItem::firstOrNew([
                'company_id' => $companyId,
                'reference_type' => 'inventory_item',
                'reference_id' => $item->id,
            ]);

            $settings = $this->xeroSettings($companyId);
            $code = $xeroItem->code ?: ($item->sku ?: ('ITEM-' . $item->id));
            $taxType = $settings['default_tax_type'];

            $xeroItem->code = $code;
            $xeroItem->name = $item->name;
            $xeroItem->is_tracked_as_inventory = true;
            $xeroItem->inventory_asset_account_code = $settings['inventory_asset_account_code'];
            $xeroItem->purchase_account_code = $settings['inventory_cogs_account_code'];
            $xeroItem->purchase_unit_price = $item->unit_price;
            $xeroItem->purchase_tax_type = $taxType;
            $xeroItem->sales_account_code = $settings['inventory_sales_account_code'];
            $xeroItem->sales_unit_price = $item->unit_price;
            $xeroItem->sales_tax_type = $taxType;
            $xeroItem->status = 'pending';
            $xeroItem->error = null;
            $xeroItem->save();

            $payload = [
                'Code' => $code,
                'Name' => substr($item->name, 0, 50),
                'IsTrackedAsInventory' => true,
                'InventoryAssetAccountCode' => $xeroItem->inventory_asset_account_code,
                'PurchaseDetails' => [
                    'UnitPrice' => (float) $item->unit_price,
                    'AccountCode' => $xeroItem->purchase_account_code,
                    'TaxType' => $taxType,
                ],
                'SalesDetails' => [
                    'UnitPrice' => (float) $item->unit_price,
                    'AccountCode' => $xeroItem->sales_account_code,
                    'TaxType' => $taxType,
                ],
            ];

            if ($xeroItem->xero_item_id) {
                $payload['ItemID'] = $xeroItem->xero_item_id;
            }

            $result = $this->xeroService->createOrUpdateItem($connection, $payload);

            $connection->update(['last_synced_at' => now()]);

            $xeroItem->update([
                'xero_item_id' => $result['Items'][0]['ItemID'] ?? $xeroItem->xero_item_id,
                'quantity_on_hand' => $result['Items'][0]['QuantityOnHand'] ?? null,
                'status' => 'synced',
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Inventory item synced to Xero',
                'item_id' => $xeroItem->xero_item_id,
            ];

        } catch (\Exception $e) {
            Log::error('Xero sync inventory item failed', [
                'inventory_item_id' => $inventoryItemId,
                'error' => $e->getMessage(),
            ]);

            if (isset($xeroItem)) {
                $xeroItem->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'Failed to sync inventory item: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function pushPendingInventoryItems(User $user): array
    {
        return $this->pushPendingInventoryItemsForCompany($user->company_id);
    }

    public function pushPendingInventoryItemsForAllCompanies(): array
    {
        $companyIds = XeroConnection::active()->pluck('company_id');

        $perCompany = $companyIds->map(function ($companyId) {
            return array_merge(['company_id' => $companyId], $this->pushPendingInventoryItemsForCompany($companyId));
        });

        return $this->summarizeAcrossCompanies($perCompany);
    }

    /**
     * Flatten per-company push results into one clear, non-nested summary:
     * total/pushed/failed counts plus a single flat list of every record's outcome.
     */
    protected function summarizeAcrossCompanies($perCompanyResults): array
    {
        $items = $perCompanyResults->flatMap(function ($companyResult) {
            $companyId = $companyResult['company_id'];

            return collect($companyResult['results'] ?? [])->map(function ($item) use ($companyId) {
                return array_merge(['company_id' => $companyId], $item);
            });
        })->values();

        return [
            'success' => true,
            'companies' => $perCompanyResults->count(),
            'total' => $items->count(),
            'pushed' => $items->where('success', true)->count(),
            'failed' => $items->where('success', false)->count(),
            'results' => $items,
        ];
    }

    protected function pushPendingInventoryItemsForCompany(int $companyId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        $pendingItemIds = InventoryItem::where('inventory_items.company_id', $companyId)
            ->where('inventory_items.is_active', true)
            ->leftJoin('xero_items', function ($join) {
                $join->on('xero_items.reference_id', '=', 'inventory_items.id')
                    ->where('xero_items.reference_type', '=', 'inventory_item')
                    ->where('xero_items.status', '=', 'synced');
            })
            ->whereNull('xero_items.id')
            ->pluck('inventory_items.id');

        $results = $pendingItemIds->map(function ($itemId) use ($companyId) {
            return array_merge(['inventory_item_id' => $itemId], $this->syncInventoryItemForCompany($companyId, $itemId));
        });

        return [
            'success' => true,
            'total' => $results->count(),
            'pushed' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'results' => $results->values(),
        ];
    }

    public function syncService(User $user, int $serviceId): array
    {
        return $this->syncServiceForCompany($user->company_id, $serviceId);
    }

    /**
     * Push a Service into Xero's Items catalog as a non-tracked, sales-only item.
     * Services have no company_id (shared catalog), but each company's Xero org
     * still needs its own Item record, so this is scoped per company like everything else.
     */
    protected function syncServiceForCompany(int $companyId, int $serviceId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
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
            $service = Service::findOrFail($serviceId);

            $xeroItem = XeroItem::firstOrNew([
                'company_id' => $companyId,
                'reference_type' => 'service',
                'reference_id' => $service->id,
            ]);

            $settings = $this->xeroSettings($companyId);
            $code = $xeroItem->code ?: ('SVC-' . $service->id);
            $taxType = $settings['default_tax_type'];

            $xeroItem->code = $code;
            $xeroItem->name = $service->name;
            $xeroItem->is_tracked_as_inventory = false;
            $xeroItem->inventory_asset_account_code = null;
            $xeroItem->purchase_account_code = null;
            $xeroItem->purchase_unit_price = null;
            $xeroItem->purchase_tax_type = null;
            $xeroItem->sales_account_code = $settings['service_sales_account_code'];
            $xeroItem->sales_unit_price = $service->price;
            $xeroItem->sales_tax_type = $taxType;
            $xeroItem->status = 'pending';
            $xeroItem->error = null;
            $xeroItem->save();

            $payload = [
                'Code' => $code,
                'Name' => substr($service->name, 0, 50),
                'IsTrackedAsInventory' => false,
                'SalesDetails' => [
                    'UnitPrice' => (float) $service->price,
                    'AccountCode' => $xeroItem->sales_account_code,
                    'TaxType' => $taxType,
                ],
            ];

            if ($xeroItem->xero_item_id) {
                $payload['ItemID'] = $xeroItem->xero_item_id;
            }

            $result = $this->xeroService->createOrUpdateItem($connection, $payload);

            $connection->update(['last_synced_at' => now()]);

            $xeroItem->update([
                'xero_item_id' => $result['Items'][0]['ItemID'] ?? $xeroItem->xero_item_id,
                'status' => 'synced',
                'synced_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Service synced to Xero',
                'item_id' => $xeroItem->xero_item_id,
            ];

        } catch (\Exception $e) {
            Log::error('Xero sync service failed', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);

            if (isset($xeroItem)) {
                $xeroItem->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'Failed to sync service: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function pushPendingServices(User $user): array
    {
        return $this->pushPendingServicesForCompany($user->company_id);
    }

    public function pushPendingServicesForAllCompanies(): array
    {
        $companyIds = XeroConnection::active()->pluck('company_id');

        $perCompany = $companyIds->map(function ($companyId) {
            return array_merge(['company_id' => $companyId], $this->pushPendingServicesForCompany($companyId));
        });

        return $this->summarizeAcrossCompanies($perCompany);
    }

    protected function pushPendingServicesForCompany(int $companyId): array
    {
        $connection = XeroConnection::where('company_id', $companyId)
            ->active()
            ->first();

        if (!$connection) {
            return [
                'success' => false,
                'error' => 'Not connected to Xero',
                'status_code' => 404,
            ];
        }

        $pendingServiceIds = Service::where('services.status', 1)
            ->leftJoin('xero_items', function ($join) use ($companyId) {
                $join->on('xero_items.reference_id', '=', 'services.id')
                    ->where('xero_items.reference_type', '=', 'service')
                    ->where('xero_items.company_id', '=', $companyId)
                    ->where('xero_items.status', '=', 'synced');
            })
            ->whereNull('xero_items.id')
            ->pluck('services.id');

        $results = $pendingServiceIds->map(function ($serviceId) use ($companyId) {
            return array_merge(['service_id' => $serviceId], $this->syncServiceForCompany($companyId, $serviceId));
        });

        return [
            'success' => true,
            'total' => $results->count(),
            'pushed' => $results->where('success', true)->count(),
            'failed' => $results->where('success', false)->count(),
            'results' => $results->values(),
        ];
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
