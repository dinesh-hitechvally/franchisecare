<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroBill extends Model
{
    protected $fillable = [
        'company_id',
        'reference_type',
        'reference_id',
        'xero_invoice_id',
        'xero_invoice_number',
        'status',
        'amount',
        'currency',
        'synced_at',
        'error',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
