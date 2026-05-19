<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'start_date',
        'end_date',
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
        'end_date'   => 'date',
        'total'      => 'decimal:2',
        'send_sms'   => 'boolean',
        'send_email' => 'boolean',
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
        return $this->hasMany(WaitlistDetail::class);
    }
}
