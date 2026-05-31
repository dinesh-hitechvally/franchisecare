<?php

namespace App\Contracts\Services;

use App\Models\Waitlist;
use Illuminate\Pagination\LengthAwarePaginator;

interface WaitlistServiceInterface
{
    public function listWaitlists(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    public function getWaitlist(int $id): Waitlist;

    public function createWaitlist(array $data): Waitlist;

    public function updateWaitlist(Waitlist $waitlist, array $data): Waitlist;

    public function deleteWaitlist(Waitlist $waitlist): bool;
}
