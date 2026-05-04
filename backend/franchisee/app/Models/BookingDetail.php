<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingDetail extends Model
{
    protected $fillable = [
        'company_id',
        'booking_id',
        'service_id',
        'item_id',
        'price',
        'duration',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
