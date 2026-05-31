<?php

namespace App\Services;

use App\Contracts\Repositories\StockTakeRepositoryInterface;
use App\Contracts\Services\StockTakeServiceInterface;
use App\Models\StockTakeBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StockTakeService implements StockTakeServiceInterface
{
    public function __construct(
        protected StockTakeRepositoryInterface $repository
    ) {}

    protected function getCompanyId(): int
    {
        return Auth::user()->company_id ?? Auth::user()->franchise_id;
    }

    public function getLastBatch(int $categoryId): ?StockTakeBatch
    {
        return $this->repository->getLastBatch($this->getCompanyId(), $categoryId);
    }

    public function getHistory(int $categoryId): Collection
    {
        return $this->repository->getHistory($this->getCompanyId(), $categoryId, 100);
    }

    public function getCurrentSoh(int $categoryId): Collection
    {
        return $this->repository->getCurrentSoh($this->getCompanyId(), $categoryId);
    }

    public function store(int $categoryId, array $values): StockTakeBatch
    {
        return $this->repository->createStockTake(
            $this->getCompanyId(),
            $categoryId,
            Auth::id(),
            $values
        );
    }
}
