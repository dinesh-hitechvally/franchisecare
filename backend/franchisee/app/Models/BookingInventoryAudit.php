<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingInventoryAudit extends Model
{
    protected $fillable = [
        'booking_id',
        'company_id',
        'inventory_id',
        'inventory_item_name',
        'change_type',
        'quantity_before',
        'quantity_after',
        'quantity_change',
        'notes',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}