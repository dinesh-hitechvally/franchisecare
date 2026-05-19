<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingDetailAudit extends Model
{
    protected $fillable = [
        'booking_detail_id',
        'booking_id',
        'company_id',
        'action_type',
        'action_at',
        'service_id',
        'item_id',
        'price',
        'duration',
        'performed_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
