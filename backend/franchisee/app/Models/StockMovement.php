<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movement';

    protected $fillable = [
        'company_id',
        'category_id',
        'inventory_id',
        'batch_id',
        'movement_type',
        'quantity_change',
        'percentage_change',
        'quantity_before',
        'quantity_after',
        'percentage_before',
        'percentage_after',
        'reference_type',
        'reference_id',
        'notes',
        'performed_by',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'percentage_change' => 'decimal:2',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'percentage_before' => 'decimal:2',
        'percentage_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function inventory()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function batch()
    {
        return $this->belongsTo(StockTakeBatch::class, 'batch_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
