<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOrderItem extends Model
{
    protected $fillable = [
        'inventory_order_id',
        'inventory_item_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(InventoryOrder::class, 'inventory_order_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
