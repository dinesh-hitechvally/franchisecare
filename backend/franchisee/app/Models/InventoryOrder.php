<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryOrder extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'order_number',
        'type',
        'status',
        'total',
        'notes',
        'ordered_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'ordered_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = 'ORD-' . strtoupper(uniqid());
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(InventoryOrderItem::class);
    }
}
