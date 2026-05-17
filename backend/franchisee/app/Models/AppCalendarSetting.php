<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppCalendarSetting extends Model
{
    protected $fillable = [
        'company_id',
        'show_customer_name',
        'show_customer_address',
        'show_booking_total',
        'show_time',
        'show_pet_name',
        'show_services_name',
        'show_pet_breed',
        'display_order',
    ];

    protected $casts = [
        'show_customer_name' => 'boolean',
        'show_customer_address' => 'boolean',
        'show_booking_total' => 'boolean',
        'show_time' => 'boolean',
        'show_pet_name' => 'boolean',
        'show_services_name' => 'boolean',
        'show_pet_breed' => 'boolean',
        'display_order' => 'array',
    ];
}
