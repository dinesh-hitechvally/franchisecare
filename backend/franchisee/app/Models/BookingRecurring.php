<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingRecurring extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'start_date',
        'repeat_time',
        'frequency',
        'repeat_day',
        'status',
        'auto_extend',
        'total',
        'duration',
        'color',
        'notes',
        'cancelled_date',
        'cancellation_reason',
        'repeat_until',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total' => 'decimal:2',
        'auto_extend' => 'boolean',
        'cancelled_date' => 'date',
        'repeat_until' => 'date',
        'frequency' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function details()
    {
        return $this->hasMany(BookingRecurringDetail::class, 'recurring_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'recurring_id');
    }
}
