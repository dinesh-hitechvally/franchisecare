<?php

namespace App\Services;

use App\Contracts\Repositories\BlockoutRecurringRepositoryInterface;
use App\Contracts\Services\BlockoutRecurringServiceInterface;
use App\Models\BlockoutRecurring;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BlockoutRecurringService implements BlockoutRecurringServiceInterface
{
    public function __construct(
        protected BlockoutRecurringRepositoryInterface $repository
    ) {}

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function create(array $data): BlockoutRecurring
    {
        if (empty($data['company_id']) && Auth::check()) {
            $data['company_id'] = Auth::user()?->company_id;
        }

        if (empty($data['company_id'])) {
            throw new \InvalidArgumentException('Company information is required');
        }

        return $this->repository->create($data);
    }

    public function update(BlockoutRecurring $recurring, array $data): BlockoutRecurring
    {
        return $this->repository->update($recurring, $data);
    }

    public function delete(BlockoutRecurring $recurring): void
    {
        $this->repository->delete($recurring);
    }

    public function getHistory(BlockoutRecurring $recurring): LengthAwarePaginator
    {
        return $this->repository->getHistory($recurring, 10);
    }
}
