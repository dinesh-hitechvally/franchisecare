<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockoutAudit extends Model
{
    protected $fillable = [
        'blockout_id',
        'company_id',
        'action_type',
        'action_at',
        'title',
        'location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'recurring_id',
        'notes',
        'active',
        'performed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'recurring_id' => 'integer',
        'active' => 'boolean',
    ];
}
