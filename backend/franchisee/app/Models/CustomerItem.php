<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerItem extends Model
{
    protected $fillable = [
        'name',
        'gender',
        'birth_date',
        'breed',
        'size',
        'image',
        'allergies',
        'notes',
        'customer_id',
        'company_id',
        'signature',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class);
    }

    public function audits()
    {
        return $this->hasMany(CustomerItemAudit::class, 'item_id');
    }
}
