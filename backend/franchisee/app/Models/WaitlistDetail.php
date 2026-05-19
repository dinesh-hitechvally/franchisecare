<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistDetail extends Model
{
    protected $fillable = [
        'company_id',
        'waitlist_id',
        'service_id',
        'item_id',
        'price',
        'duration',
    ];

    public function waitlist()
    {
        return $this->belongsTo(Waitlist::class);
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
