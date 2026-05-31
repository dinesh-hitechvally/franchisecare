<?php

namespace App\Contracts\Services;

use App\Models\StockTakeBatch;
use Illuminate\Support\Collection;

interface StockTakeServiceInterface
{
    public function getLastBatch(int $categoryId): ?StockTakeBatch;
    public function getHistory(int $categoryId): Collection;
    public function getCurrentSoh(int $categoryId): Collection;
    public function store(int $categoryId, array $values): StockTakeBatch;
}
