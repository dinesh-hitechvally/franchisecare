<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'logo',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function bookingRecurrings()
    {
        return $this->hasMany(BookingRecurring::class);
    }

    public function customerItems()
    {
        return $this->hasMany(CustomerItem::class);
    }

    public function userServices()
    {
        return $this->hasMany(UserService::class);
    }
}
