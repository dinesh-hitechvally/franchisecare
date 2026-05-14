<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryOrder::with(['items', 'user'])
            ->where('company_id', auth()->user()->company_id)
            ->orderByDesc('created_at');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->input('per_page', 25);
        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:inventory,treats,marketing',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.product_sku' => 'nullable|string|max:100',
            'items.*.inventory_item_id' => 'nullable|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $total = collect($validated['items'])->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $order = InventoryOrder::create([
                'company_id' => auth()->user()->company_id,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'status' => 'pending',
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
                'ordered_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                InventoryOrderItem::create([
                    'inventory_order_id' => $order->id,
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return response()->json([
                'message' => 'Order created successfully',
                'data' => $order->load('items'),
            ], 201);
        });
    }

    public function show(InventoryOrder $inventoryOrder)
    {
        $inventoryOrder->load(['items', 'user']);
        return response()->json($inventoryOrder);
    }

    public function update(Request $request, InventoryOrder $inventoryOrder)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,shipped,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'shipped' && !$inventoryOrder->shipped_at) {
                $validated['shipped_at'] = now();
            }
            if ($validated['status'] === 'delivered' && !$inventoryOrder->delivered_at) {
                $validated['delivered_at'] = now();
            }
        }

        $inventoryOrder->update($validated);

        return response()->json([
            'message' => 'Order updated successfully',
            'data' => $inventoryOrder->fresh(['items', 'user']),
        ]);
    }

    public function destroy(InventoryOrder $inventoryOrder)
    {
        if ($inventoryOrder->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be deleted',
            ], 422);
        }

        $inventoryOrder->delete();

        return response()->json(null, 204);
    }
}
