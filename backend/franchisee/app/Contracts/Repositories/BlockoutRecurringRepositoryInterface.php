<?php

namespace App\Contracts\Repositories;

use App\Models\BlockoutRecurring;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BlockoutRecurringRepositoryInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function create(array $data): BlockoutRecurring;
    public function update(BlockoutRecurring $recurring, array $data): BlockoutRecurring;
    public function delete(BlockoutRecurring $recurring): void;
    public function getHistory(BlockoutRecurring $recurring, int $perPage): LengthAwarePaginator;
    public function regenerateBlockouts(BlockoutRecurring $recurring): void;
}
