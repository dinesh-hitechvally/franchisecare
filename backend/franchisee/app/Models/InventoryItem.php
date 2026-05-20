<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'sku',
        'quantity',
        'min_stock',
        'unit_price',
        'unit_id',
        'notes',
        'is_active',
        'booking_usage',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
        'booking_usage' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}