<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'attach_policy',
        'cancel_before_unit',
        'cancel_before_value',
        'cancellation_fee_value',
        'penalty_type',
        'selected_policy_id',
        'policy_text',
    ];

    protected $casts = [
        'attach_policy' => 'boolean',
        'cancel_before_value' => 'integer',
        'cancellation_fee_value' => 'decimal:2',
        'selected_policy_id' => 'integer',
    ];
}
