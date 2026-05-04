<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'size',
        'price',
        'duration',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class);
    }

    public function userServices()
    {
        return $this->hasMany(UserService::class);
    }
}
