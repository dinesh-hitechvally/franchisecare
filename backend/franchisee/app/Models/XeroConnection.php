<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroConnection extends Model
{
    protected $fillable = [
        'company_id',
        'tenant_id',
        'tenant_name',
        'tenant_type',
        'access_token',
        'refresh_token',
        'expires_at',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Get the company that owns this Xero connection
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the access token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the connection is valid and active
     */
    public function isValid(): bool
    {
        return $this->is_active && !empty($this->refresh_token);
    }

    /**
     * Scope to get active connections
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
