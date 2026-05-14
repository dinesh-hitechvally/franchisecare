<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceInventoryUsage extends Model
{
    protected $fillable = [
        'company_id',
        'service_id',
        'inventory_name',
        'quantity_per_booking',
        'unit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity_per_booking' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}