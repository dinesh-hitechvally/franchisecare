<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRecurringDetailAudit extends Model
{
    protected $fillable = [
        'booking_recurring_detail_id',
        'recurring_id',
        'customer_id',
        'company_id',
        'action_type',
        'action_at',
        'item_id',
        'service_id',
        'price',
        'duration',
        'performed_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];
}
