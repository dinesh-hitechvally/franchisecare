<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTakeLog extends Model
{
    use HasFactory;

    protected $table = 'stock_take_log';
    protected $primaryKey = 'id';
    protected $fillable = [
        'inventory_id',
        'inventory_category_id',
        'companyID',
        'no_of_bookings',
        'current_stock',
        'remaining_percent',
        'log_quantity',
        'log_remaining_percent',
        'created_at',
        'updated_at',
    ];
    public $timestamps = true;

    protected $dates = ['created_at', 'updated_at'];

    public function inventory()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_id');
    }
}
