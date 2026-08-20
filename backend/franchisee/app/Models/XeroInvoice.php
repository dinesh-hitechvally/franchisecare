<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'reference_type',
        'reference_id',
        'xero_invoice_id',
        'xero_invoice_number',
        'invoice_type',
        'status',
        'amount',
        'currency',
        'synced_at',
        'paid_at',
        'error',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'synced_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the Xero connection for this invoice's company (matched via company_id)
     */
    public function xeroConnection(): BelongsTo
    {
        return $this->belongsTo(XeroConnection::class, 'company_id', 'company_id');
    }
}
