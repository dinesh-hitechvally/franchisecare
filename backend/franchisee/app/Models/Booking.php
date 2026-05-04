<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'recurring_id',
        'start_date',
        'start_time',
        'end_time',
        'status',
        'total',
        'duration',
        'calendar_color',
        'send_sms',
        'send_email',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total' => 'decimal:2',
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
        return $this->hasMany(BookingDetail::class);
    }

    public function recurring()
    {
        return $this->belongsTo(BookingRecurring::class, 'recurring_id');
    }
}
