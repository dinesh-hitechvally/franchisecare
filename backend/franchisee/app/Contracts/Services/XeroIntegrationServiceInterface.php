<?php

namespace App\Contracts\Services;

use App\Models\User;

interface XeroIntegrationServiceInterface
{
    public function status(User $user): array;

    public function authorize(User $user): array;

    public function callback(User $user, string $code, string $state, ?string $scope = null): array;

    public function disconnect(User $user): array;

    public function accounts(User $user): array;

    public function createAccount(User $user, array $data): array;

    public function taxRates(User $user): array;

    public function getSettings(User $user): array;

    public function updateSettings(User $user, array $data): array;

    public function syncBooking(User $user, int $bookingId): array;

    public function pushPendingBookings(User $user): array;

    public function pushPendingBookingsForAllCompanies(): array;

    public function syncCustomer(User $user, int $customerId): array;

    public function pushPendingCustomers(User $user): array;

    public function pushPendingCustomersForAllCompanies(): array;

    public function syncPurchase(User $user, int $orderId): array;

    public function pushPendingPurchases(User $user): array;

    public function pushPendingPurchasesForAllCompanies(): array;

    public function syncInventoryItem(User $user, int $inventoryItemId): array;

    public function pushPendingInventoryItems(User $user): array;

    public function pushPendingInventoryItemsForAllCompanies(): array;

    public function syncService(User $user, int $serviceId): array;

    public function pushPendingServices(User $user): array;

    public function pushPendingServicesForAllCompanies(): array;

    public function test(User $user): array;
}
