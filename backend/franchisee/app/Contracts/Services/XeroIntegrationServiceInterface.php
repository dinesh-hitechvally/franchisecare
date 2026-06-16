<?php

namespace App\Contracts\Services;

use App\Models\User;

interface XeroIntegrationServiceInterface
{
    public function status(User $user): array;

    public function authorize(User $user): array;

    public function callback(User $user, string $code, string $state): array;

    public function disconnect(User $user): array;

    public function accounts(User $user): array;

    public function syncBooking(User $user, int $bookingId): array;

    public function test(User $user): array;
}
