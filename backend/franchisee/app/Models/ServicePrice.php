<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'my_price',
        'default_price',
        'color',
        'my_time',
        'default_time',
    ];

    protected $casts = [
        'my_price' => 'decimal:2',
        'default_price' => 'decimal:2',
        'my_time' => 'integer',
        'default_time' => 'integer',
    ];
}
