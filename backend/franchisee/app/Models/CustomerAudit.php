<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAudit extends Model
{
    protected $fillable = [
        'customer_id',
        'action_type',
        'first_name',
        'last_name',
        'email',
        'other_email',
        'phone',
        'other_phone',
        'address',
        'street_address',
        'suburb',
        'postcode',
        'state',
        'company_id',
        'notes',
        'referred_by',
        'is_ndis',
        'is_subscribed',
        'is_active',
        'latitude',
        'longitude',
        'reference_id',
        'is_archived',
    ];

    protected $casts = [
        'is_ndis' => 'boolean',
        'is_subscribed' => 'boolean',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Get the customer that owns the audit.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
