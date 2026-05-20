<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FranchiseAudit extends Model
{
    protected $fillable = [
        'franchise_id',
        'user_id',
        'action',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
