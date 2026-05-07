<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $fillable = [
        'company_id',
        'event_type',
        'title',
        'description',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'color',
        'location',
        'customer_id',
        'booking_id',
        'blockout_id',
        'is_recurring',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function blockout()
    {
        return $this->belongsTo(Blockout::class);
    }
}
