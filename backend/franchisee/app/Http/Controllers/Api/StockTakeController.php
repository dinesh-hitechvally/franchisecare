<?php

namespace App\Http\Controllers\Api;

use App\Models\StockTake;
use App\Models\StockTakeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTakeController extends Controller
{
    /**
     * Get the last stock take for a category
     */
    public function getLast($categoryId)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;

        $stockTake = StockTake::where('inventory_category_id', $categoryId)
            ->where('companyID', $companyId)
            ->orderBy('updated_at', 'desc')
            ->first();

        return response()->json($stockTake ?? new StockTake());
    }

    /**
     * Get stock take history for a category
     */
    public function getHistory($categoryId)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;

        $history = DB::table('stock_take_log as stl')
            ->leftJoin('inventory_items as ii', 'stl.inventory_id', '=', 'ii.id')
            ->where('stl.inventory_category_id', $categoryId)
            ->where('stl.companyID', $companyId)
            ->select(
                'stl.id as logID',
                'stl.inventory_id',
                'ii.name as inventory_item_name',
                'stl.no_of_bookings',
                'stl.current_stock',
                'stl.remaining_percent',
                'stl.log_quantity',
                'stl.log_remaining_percent',
                'stl.created_at as log_created_at'
            )
            ->orderBy('stl.created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($history);
    }

    /**
     * Submit stock take data
     */
    public function store(Request $request)
    {
        $companyId = Auth::user()->company_id ?? Auth::user()->franchise_id;
        $categoryId = $request->input('inventory_category_id');
        $values = $request->input('values', []);

        try {
            DB::beginTransaction();

            // Create or update stock take record
            $stockTake = StockTake::updateOrCreate(
                [
                    'inventory_category_id' => $categoryId,
                    'companyID' => $companyId,
                ],
                [
                    'appID' => $companyId,
                    'companyID' => $companyId,
                    'updated_at' => now(),
                ]
            );

            // Store individual stock take logs
            foreach ($values as $inventoryId => $data) {
                StockTakeLog::create([
                    'inventory_id' => $inventoryId,
                    'inventory_category_id' => $categoryId,
                    'companyID' => $companyId,
                    'log_quantity' => $data['qty'] ?? 0,
                    'log_remaining_percent' => $data['percent'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Stock take submitted successfully',
                'data' => $stockTake,
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
