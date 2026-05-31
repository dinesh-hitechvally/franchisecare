<?php

namespace App\Contracts\Repositories;

use App\Models\StockTakeBatch;
use Illuminate\Support\Collection;

interface StockTakeRepositoryInterface
{
    public function getLastBatch(int $companyId, int $categoryId): ?StockTakeBatch;
    public function getHistory(int $companyId, int $categoryId, int $limit): Collection;
    public function getCurrentSoh(int $companyId, int $categoryId): Collection;
    public function createStockTake(int $companyId, int $categoryId, int $userId, array $values): StockTakeBatch;
}
