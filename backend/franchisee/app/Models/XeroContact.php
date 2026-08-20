<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XeroContact extends Model
{
    protected $fillable = [
        'company_id',
        'reference_type',
        'reference_id',
        'xero_contact_id',
        'name',
        'email',
        'status',
        'synced_at',
        'error',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
