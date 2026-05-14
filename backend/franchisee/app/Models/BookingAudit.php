<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAudit extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
        'company_id',
        'action_type',
        'previous_status',
        'status',
        'start_date',
        'start_time',
        'end_time',
        'total',
        'duration',
        'calendar_color',
        'send_sms',
        'send_email',
        'notes',
        'details_summary',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total' => 'decimal:2',
        'send_sms' => 'boolean',
        'send_email' => 'boolean',
        'details_summary' => 'array',
        'meta' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}