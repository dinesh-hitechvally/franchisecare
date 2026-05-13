<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'customer_name',
        'email',
        'phone',
        'alternate_phone',
        'interested_services',
        'address',
        'suburb',
        'postcode',
        'pet_breed',
        'referred_by',
        'additional_note',
        'notes',
        'source',
        'leads_from',
        'status',
        'snoozed_until',
    ];

    protected $casts = [
        'snoozed_until' => 'datetime',
    ];
}
