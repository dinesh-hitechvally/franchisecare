<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'balance',
        'total_purchased',
        'total_used',
    ];

    protected $casts = [
        'balance' => 'integer',
        'total_purchased' => 'integer',
        'total_used' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
