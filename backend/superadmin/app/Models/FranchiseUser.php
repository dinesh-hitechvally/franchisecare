<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FranchiseUser extends Model
{
    protected $fillable = [
        'franchise_id',
        'name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'avatar',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }
}
