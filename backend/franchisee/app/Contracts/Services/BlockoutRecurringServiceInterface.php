<?php

namespace App\Contracts\Services;

use App\Models\BlockoutRecurring;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BlockoutRecurringServiceInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): BlockoutRecurring;
    public function update(BlockoutRecurring $recurring, array $data): BlockoutRecurring;
    public function delete(BlockoutRecurring $recurring): void;
    public function getHistory(BlockoutRecurring $recurring): LengthAwarePaginator;
}
