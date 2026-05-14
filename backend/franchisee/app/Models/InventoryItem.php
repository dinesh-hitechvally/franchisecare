<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'category',
        'sku',
        'quantity',
        'min_stock',
        'unit_price',
        'unit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}