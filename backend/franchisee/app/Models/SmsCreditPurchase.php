<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCreditPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'package_id',
        'quantity',
        'amount',
        'status',
        'purchased_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
