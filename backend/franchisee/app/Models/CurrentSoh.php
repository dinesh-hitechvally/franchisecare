<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentSoh extends Model
{
    use HasFactory;

    protected $table = 'current_soh';

    protected $fillable = [
        'company_id',
        'category_id',
        'inventory_id',
        'current_quantity',
        'current_percentage',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'current_percentage' => 'decimal:2',
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
}
