<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReminderSetting extends Model
{
    protected $fillable = [
        'company_id',
        'reminder_method',
        'send_before_hours',
    ];

    protected $casts = [
        'send_before_hours' => 'integer',
    ];
}
