<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    protected $fillable = [
        'company_id',
        'display_customer_notes',
        'hide_expired_bookings',
        'hide_booking_cash_notifications',
        'hide_past_bookings',
        'filter_services_by_pet_size',
        'display_booking_end_time',
        'show_address_in_invoice',
        'show_personal_phone',
        'time_format',
        'date_format',
    ];

    protected $casts = [
        'display_customer_notes' => 'boolean',
        'hide_expired_bookings' => 'boolean',
        'hide_booking_cash_notifications' => 'boolean',
        'hide_past_bookings' => 'boolean',
        'filter_services_by_pet_size' => 'boolean',
        'display_booking_end_time' => 'boolean',
        'show_address_in_invoice' => 'boolean',
        'show_personal_phone' => 'boolean',
        'time_format' => 'string',
        'date_format' => 'string',
    ];
}
