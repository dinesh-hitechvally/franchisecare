<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockoutRecurringAudit extends Model
{
    protected $fillable = [
        'blockout_recurring_id',
        'company_id',
        'action_type',
        'action_at',
        'title',
        'location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'repeat_every',
        'repeat_on',
        'repeat_until',
        'notes',
        'active',
        'performed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'repeat_until' => 'date',
        'active' => 'boolean',
    ];
}
