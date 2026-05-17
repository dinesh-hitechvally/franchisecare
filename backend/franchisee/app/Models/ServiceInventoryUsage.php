<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceInventoryUsage extends Model
{
    protected $fillable = [
        'company_id',
        'service_id',
        'inventory_name',
        'quantity_per_booking',
        'unit_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'quantity_per_booking' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}