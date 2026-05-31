<?php

namespace App\Contracts\Services;

use App\Models\PaymentTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentServiceInterface
{
    public function getConfig(): array;

    public function generateCaptureContext(array $targetOrigins = []): array;

    public function purchaseSmsCredits(array $data): array;

    public function payInventoryOrder(array $data): array;

    public function payBooking(array $data): array;

    public function getHistory(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getTransaction(int $id): PaymentTransaction;

    public function refundTransaction(PaymentTransaction $transaction, ?float $amount = null): array;
}
