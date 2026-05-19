<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRecurringAudit extends Model
{
    protected $fillable = [
        'booking_recurring_id',
        'customer_id',
        'company_id',
        'action_type',
        'action_at',
        'previous_status',
        'status',
        'start_date',
        'repeat_time',
        'frequency',
        'repeat_day',
        'auto_extend',
        'total',
        'duration',
        'color',
        'notes',
        'cancelled_date',
        'cancellation_reason',
        'repeat_until',
        'performed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'cancelled_date' => 'date',
        'repeat_until' => 'date',
        'auto_extend' => 'boolean',
        'total' => 'decimal:2',
    ];
}
