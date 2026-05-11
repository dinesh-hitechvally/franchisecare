<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsHistory extends Model
{
    use HasFactory;

    protected $table = 'sms_history';

    protected $fillable = [
        'user_id',
        'to_number',
        'customer_name',
        'message',
        'status',
        'gateway_response',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
