<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarSetting extends Model
{
    protected $fillable = [
        'company_id',
        'show_booking_total',
        'show_customer_name',
        'show_customer_address',
        'show_pet_name',
        'show_pet_breed',
        'show_services_name',
    ];

    protected $casts = [
        'show_booking_total' => 'boolean',
        'show_customer_name' => 'boolean',
        'show_customer_address' => 'boolean',
        'show_pet_name' => 'boolean',
        'show_pet_breed' => 'boolean',
        'show_services_name' => 'boolean',
    ];
}
