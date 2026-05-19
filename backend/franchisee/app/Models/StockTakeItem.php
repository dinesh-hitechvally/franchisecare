<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTakeItem extends Model
{
    use HasFactory;

    protected $table = 'stock_take_items';

    protected $fillable = [
        'company_id',
        'category_id',
        'batch_id',
        'inventory_id',
        'available_quantity',
        'available_percentage',
    ];

    protected $casts = [
        'available_quantity' => 'integer',
        'available_percentage' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(StockTakeBatch::class, 'batch_id');
    }

    public function inventory()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }
}
