<?php

namespace App\Contracts\Services;

use App\Models\Customer;
use Illuminate\Contracts\Auth\Authenticatable;

interface SmsServiceInterface
{
    public function send(Authenticatable $user, array $data): array;

    public function sendBulk(Authenticatable $user, array $data): array;

    public function sendToCustomer(Authenticatable $user, Customer $customer, string $message): array;

    public function getStatus(): array;

    public function getMessageStatus(string $messageId): array;

    public function calculateParts(string $message): array;

    public function isConfigured(): bool;
}
