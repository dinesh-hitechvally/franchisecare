<?php

namespace App\Repositories;

use App\Contracts\Repositories\InventoryOrderRepositoryInterface;
use App\Models\InventoryOrder;
use App\Models\InventoryOrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryOrderRepository implements InventoryOrderRepositoryInterface
{
    public function paginate(int $companyId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryOrder::with(['items', 'user'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): InventoryOrder
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $total = collect($items)->sum(function ($item) {
                return $item['quantity'] * $item['unit_price'];
            });

            $data['total'] = $total;
            $data['status'] = 'pending';
            $data['ordered_at'] = now();

            $order = InventoryOrder::create($data);

            foreach ($items as $item) {
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

            return $order->load('items');
        });
    }

    public function update(InventoryOrder $order, array $data): InventoryOrder
    {
        if (isset($data['status'])) {
            if ($data['status'] === 'shipped' && !$order->shipped_at) {
                $data['shipped_at'] = now();
            }
            if ($data['status'] === 'delivered' && !$order->delivered_at) {
                $data['delivered_at'] = now();
            }
        }

        $order->update($data);
        return $order->fresh(['items', 'user']);
    }

    public function delete(InventoryOrder $order): void
    {
        $order->delete();
    }
}
