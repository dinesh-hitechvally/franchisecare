<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailHistory extends Model
{
    use HasFactory;

    protected $table = 'email_history';

    protected $fillable = [
        'user_id',
        'from_email',
        'to_email',
        'subject',
        'body',
        'status',
        'mailer_response',
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
