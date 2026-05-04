<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'other_email',
        'phone',
        'other_phone',
        'address',
        'street_address',
        'suburb',
        'postcode',
        'state',
        'company_id',
        'notes',
        'referred_by',
        'is_ndis',
        'is_subscribed',
        'is_active',
        'latitude',
        'longitude',
        'reference_id',
        'is_archived',
    ];

    protected $casts = [
        'is_ndis' => 'boolean',
        'is_subscribed' => 'boolean',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($customer) {
            if ($customer->isDirty(['street_address', 'suburb', 'postcode', 'state'])) {
                $components = [
                    $customer->street_address,
                    trim(($customer->suburb ?? "") . " " . ($customer->postcode ?? "") . " " . ($customer->state ?? ""))
                ];
                $customer->address = implode(', ', array_filter($components));
            }
        });
    }

    public function customerItems()
    {
        return $this->hasMany(CustomerItem::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function userServices()
    {
        return $this->hasMany(UserService::class);
    }
}
