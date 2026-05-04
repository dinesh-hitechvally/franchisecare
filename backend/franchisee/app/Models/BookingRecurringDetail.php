<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingRecurringDetail extends Model
{
    protected $fillable = [
        'company_id',
        'customer_id',
        'recurring_id',
        'item_id',
        'service_id',
        'price',
        'duration',
    ];

    public function bookingRecurring()
    {
        return $this->belongsTo(BookingRecurring::class, 'recurring_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function item()
    {
        return $this->belongsTo(CustomerItem::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
