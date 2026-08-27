<?php

namespace App\Repositories;

use App\Contracts\Repositories\StockTakeRepositoryInterface;
use App\Models\CurrentSoh;
use App\Models\StockMovement;
use App\Models\StockTakeBatch;
use App\Models\StockTakeItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockTakeRepository implements StockTakeRepositoryInterface
{
    public function getLastBatch(int $companyId, int $categoryId): ?StockTakeBatch
    {
        return StockTakeBatch::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with('items.inventory')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function getHistory(int $companyId, int $categoryId, int $limit): Collection
    {
        return StockMovement::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with(['inventory', 'batch', 'performer'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCurrentSoh(int $companyId, int $categoryId): Collection
    {
        return CurrentSoh::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with('inventory')
            ->get();
    }

    public function createStockTake(int $companyId, int $categoryId, int $userId, array $values): StockTakeBatch
    {
        return DB::transaction(function () use ($companyId, $categoryId, $userId, $values) {
            $batch = StockTakeBatch::create([
                'company_id' => $companyId,
                'category_id' => $categoryId,
            ]);

            foreach ($values as $inventoryId => $data) {
                $quantity = (int) ($data['qty'] ?? $data['quantity'] ?? 0);
                $percentage = (float) ($data['percent'] ?? $data['percentage'] ?? 0);

                $currentSoh = CurrentSoh::where('company_id', $companyId)
                    ->where('inventory_id', $inventoryId)
                    ->first();

                $quantityBefore = $currentSoh?->current_quantity ?? 0;
                $percentageBefore = $currentSoh?->current_percentage ?? 0;

                StockTakeItem::create([
                    'company_id' => $companyId,
                    'category_id' => $categoryId,
                    'batch_id' => $batch->id,
                    'inventory_id' => $inventoryId,
                    'available_quantity' => $quantity,
                    'available_percentage' => $percentage,
                ]);

                CurrentSoh::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'inventory_id' => $inventoryId,
                    ],
                    [
                        'category_id' => $categoryId,
                        'current_quantity' => $quantity,
                        'current_percentage' => $percentage,
                    ]
                );

                StockMovement::create([
                    'company_id' => $companyId,
                    'category_id' => $categoryId,
                    'inventory_id' => $inventoryId,
                    'batch_id' => $batch->id,
                    'movement_type' => 'STOCK_TAKE',
                    'quantity_change' => $quantity - $quantityBefore,
                    'percentage_change' => $percentage - $percentageBefore,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantity,
                    'percentage_before' => $percentageBefore,
                    'percentage_after' => $percentage,
                    'reference_type' => StockTakeBatch::class,
                    'reference_id' => $batch->id,
                    'performed_by' => $userId,
                ]);
            }

            return $batch->load('items.inventory');
        });
    }
}
