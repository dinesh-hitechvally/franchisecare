<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class XeroOauthRequest extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'state',
        'code',
        'scope',
        'status',
        'error',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function xeroConnections(): HasMany
    {
        return $this->hasMany(XeroConnection::class);
    }
}
