<?php

namespace App\Http\Controllers\Api\Backup;

use App\Http\Controllers\Controller;
use App\Models\CurrentSoh;
use App\Models\StockMovement;
use App\Models\StockTakeBatch;
use App\Models\StockTakeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTakeController extends Controller
{
    /**
     * Get the last stock take batch for a category
     */
    public function getLast($categoryId)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;

        $batch = StockTakeBatch::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with('items.inventory')
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json($batch);
    }

    /**
     * Get stock take history for a category
     */
    public function getHistory($categoryId)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;

        $history = StockMovement::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with(['inventory', 'batch', 'performer'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($history);
    }

    /**
     * Get current SOH for a category
     */
    public function getCurrentSoh($categoryId)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;

        $soh = CurrentSoh::where('category_id', $categoryId)
            ->where('company_id', $companyId)
            ->with('inventory')
            ->get();

        return response()->json($soh);
    }

    /**
     * Submit stock take data
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;
        $userId = Auth::id();
        $categoryId = $request->input('category_id');
        $values = $request->input('values', []);

        try {
            DB::beginTransaction();

            // Create stock take batch
            $batch = StockTakeBatch::create([
                'company_id' => $companyId,
                'category_id' => $categoryId,
            ]);

            // Store individual stock take items and update current SOH
            foreach ($values as $inventoryId => $data) {
                $quantity = (int) ($data['qty'] ?? $data['quantity'] ?? 0);
                $percentage = (float) ($data['percent'] ?? $data['percentage'] ?? 0);

                // Get current SOH before update
                $currentSoh = CurrentSoh::where('company_id', $companyId)
                    ->where('inventory_id', $inventoryId)
                    ->first();

                $quantityBefore = $currentSoh?->current_quantity ?? 0;
                $percentageBefore = $currentSoh?->current_percentage ?? 0;

                // Create stock take item
                StockTakeItem::create([
                    'company_id' => $companyId,
                    'category_id' => $categoryId,
                    'batch_id' => $batch->id,
                    'inventory_id' => $inventoryId,
                    'available_quantity' => $quantity,
                    'available_percentage' => $percentage,
                ]);

                // Update current SOH
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

                // Log stock movement
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

            DB::commit();

            return response()->json([
                'message' => 'Stock take submitted successfully',
                'data' => $batch->load('items.inventory'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to submit stock take',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
